<?php

namespace App\Services;

use App\Enums\CarPoolBookingStatus;
use App\Models\CarPoolBooking;
use App\Models\CarPoolTransaction;
use App\Models\Customer;
use App\Models\CustomerWallet;
use Illuminate\Support\Facades\DB;

class CarPoolPaymentService
{
    public function __construct(
        private readonly CarPoolBookingService $bookingService
    ) {}

    /**
     * Deduct from passenger wallet and confirm booking atomically.
     */
    public function chargeWallet(CarPoolBooking $booking, Customer $customer): void
    {
        DB::transaction(function () use ($booking, $customer) {
            $wallet = CustomerWallet::where('customer_id', $customer->id)->lockForUpdate()->first();

            if (!$wallet || $wallet->balance < $booking->fare_total) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $wallet->balance -= $booking->fare_total;
            $wallet->save();

            $this->createTransaction($booking, 'wallet', 'paid');
            $this->bookingService->confirmAfterPayment($booking, 'wallet');
        });
    }

    /**
     * Initiate an online payment by creating a pending transaction.
     * Returns the transaction for reference (gateway handles callback).
     */
    public function chargeOnline(CarPoolBooking $booking): CarPoolTransaction
    {
        return $this->createTransaction($booking, 'online', 'pending');
    }

    /**
     * Called by gateway callback after successful online payment.
     */
    public function handlePaymentSuccess(CarPoolBooking $booking, string $gatewayReference): void
    {
        DB::transaction(function () use ($booking, $gatewayReference) {
            CarPoolTransaction::where('booking_id', $booking->id)
                ->where('transaction_type', 'booking_payment')
                ->update(['payment_status' => 'paid', 'gateway_reference' => $gatewayReference]);

            $this->bookingService->confirmAfterPayment($booking, 'online', $gatewayReference);
        });
    }

    /**
     * Refund payment for a cancelled booking.
     */
    public function refund(CarPoolBooking $booking): void
    {
        if ($booking->payment_status !== 'paid') {
            return;
        }

        DB::transaction(function () use ($booking) {
            if ($booking->payment_method === 'wallet') {
                $wallet = CustomerWallet::where('customer_id', $booking->passenger_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->balance += $booking->fare_total;
                    $wallet->save();
                }
            }

            // Create a refund transaction record.
            CarPoolTransaction::create([
                'booking_id'       => $booking->id,
                'route_id'         => $booking->route_id,
                'payer_id'         => $booking->passenger_id,
                'driver_id'        => $booking->route->driver_id,
                'transaction_type' => 'refund',
                'amount'           => $booking->fare_total,
                'admin_commission' => 0,
                'driver_amount'    => 0,
                'payment_method'   => $booking->payment_method,
                'payment_status'   => 'paid',
            ]);

            $booking->update(['payment_status' => 'refunded']);
        });
    }

    private function createTransaction(CarPoolBooking $booking, string $method, string $status): CarPoolTransaction
    {
        return CarPoolTransaction::create([
            'booking_id'       => $booking->id,
            'route_id'         => $booking->route_id,
            'payer_id'         => $booking->passenger_id,
            'driver_id'        => $booking->route->driver_id,
            'transaction_type' => 'booking_payment',
            'amount'           => $booking->fare_total,
            'admin_commission' => $booking->admin_commission_amount,
            'driver_amount'    => $booking->driver_amount,
            'payment_method'   => $method,
            'payment_status'   => $status,
        ]);
    }
}
