<?php

namespace App\Services;

use App\Models\AdminWallet;
use App\Models\CarPoolBooking;
use App\Models\CarPoolDriver;
use App\Models\CarPoolDriverWallet;
use App\Models\CarPoolTransaction;
use App\Models\CarPoolWithdrawalRequest;
use Illuminate\Support\Facades\DB;

class CarPoolDriverWalletService
{
    /**
     * Credit driver's pending_balance when booking is completed.
     * Also increments admin wallet commission_earned.
     */
    public function settleOnCompletion(CarPoolBooking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $driverWallet = CarPoolDriverWallet::where('driver_id', $booking->route->driver_id)
                ->lockForUpdate()->first();

            if (!$driverWallet) {
                return;
            }

            // Move earnings from pending to available.
            $driverWallet->available_balance += $booking->driver_amount;
            $driverWallet->total_earned      += $booking->driver_amount;
            $driverWallet->save();

            // Post admin commission to AdminWallet.
            $adminWallet = AdminWallet::where('admin_id', 1)->first();
            if ($adminWallet) {
                $adminWallet->commission_earned += $booking->admin_commission_amount;
                $adminWallet->save();
            }

            // Mark the booking_payment transaction as disbursed.
            CarPoolTransaction::where('booking_id', $booking->id)
                ->where('transaction_type', 'booking_payment')
                ->update(['payment_status' => 'disburse']);

            // Create a driver_credit transaction.
            CarPoolTransaction::create([
                'booking_id'       => $booking->id,
                'route_id'         => $booking->route_id,
                'payer_id'         => $booking->passenger_id,
                'driver_id'        => $booking->route->driver_id,
                'transaction_type' => 'driver_credit',
                'amount'           => $booking->driver_amount,
                'admin_commission' => $booking->admin_commission_amount,
                'driver_amount'    => $booking->driver_amount,
                'payment_method'   => $booking->payment_method,
                'payment_status'   => 'paid',
            ]);
        });
    }

    /**
     * Create a withdrawal request for a driver.
     */
    public function requestWithdrawal(CarPoolDriver $driver, float $amount, array $accountDetails): CarPoolWithdrawalRequest
    {
        $min = (float) config('carpool.min_withdrawal_amount', 10);

        if ($amount < $min) {
            throw new \RuntimeException("Minimum withdrawal amount is {$min}.");
        }

        $wallet = $driver->wallet;

        if (!$wallet || $wallet->available_balance < $amount) {
            throw new \RuntimeException('Insufficient available balance.');
        }

        return CarPoolWithdrawalRequest::create([
            'driver_id'      => $driver->id,
            'amount'         => $amount,
            'status'         => 'pending',
            'account_details'=> $accountDetails,
        ]);
    }

    /**
     * Admin approves and marks a withdrawal as paid.
     */
    public function approveWithdrawal(CarPoolWithdrawalRequest $request, string $adminNote = null): void
    {
        DB::transaction(function () use ($request, $adminNote) {
            $wallet = CarPoolDriverWallet::where('driver_id', $request->driver_id)
                ->lockForUpdate()->first();

            if (!$wallet || $wallet->available_balance < $request->amount) {
                throw new \RuntimeException('Insufficient available balance for withdrawal.');
            }

            $wallet->available_balance -= $request->amount;
            $wallet->total_withdrawn   += $request->amount;
            $wallet->save();

            $request->update([
                'status'       => 'paid',
                'admin_note'   => $adminNote,
                'processed_at' => now(),
            ]);

            // Withdrawal ledger entry.
            CarPoolTransaction::create([
                'booking_id'       => 0,
                'route_id'         => 0,
                'payer_id'         => 0,
                'driver_id'        => $request->driver_id,
                'transaction_type' => 'withdrawal',
                'amount'           => $request->amount,
                'admin_commission' => 0,
                'driver_amount'    => $request->amount,
                'payment_method'   => 'withdrawal',
                'payment_status'   => 'paid',
            ]);
        });
    }

    /**
     * Reject a pending withdrawal request.
     */
    public function rejectWithdrawal(CarPoolWithdrawalRequest $request, string $adminNote = null): void
    {
        $request->update([
            'status'       => 'rejected',
            'admin_note'   => $adminNote,
            'processed_at' => now(),
        ]);
    }
}
