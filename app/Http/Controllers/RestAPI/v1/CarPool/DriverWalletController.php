<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\RestAPI\v1\CarPool\Concerns\ResolvesCarpoolDriverFromUser;
use App\Http\Controllers\Controller;
use App\Models\CarPoolWithdrawalRequest;
use App\Services\CarPoolDriverWalletService;
use App\Events\CarPool\CarPoolWithdrawalRequestedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverWalletController extends Controller
{
    use ResolvesCarpoolDriverFromUser;

    public function __construct(private readonly CarPoolDriverWalletService $walletService) {}

    public function wallet(Request $request): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }
        $wallet = $driver->wallet;

        return response()->json([
            'status' => true,
            'wallet' => [
                'available_balance' => $wallet?->available_balance ?? 0,
                'pending_balance'   => $wallet?->pending_balance ?? 0,
                'total_earned'      => $wallet?->total_earned ?? 0,
                'total_withdrawn'   => $wallet?->total_withdrawn ?? 0,
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }

        $transactions = \App\Models\CarPoolTransaction::where('driver_id', $driver->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('limit', 15));

        return response()->json(['status' => true, 'transactions' => $transactions]);
    }

    public function requestWithdrawal(Request $request): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }

        $validator = Validator::make($request->all(), [
            'amount'          => 'required|numeric|min:0.01',
            'account_details' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $withdrawal = $this->walletService->requestWithdrawal(
                $driver,
                (float) $request->amount,
                $request->account_details
            );
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        event(new CarPoolWithdrawalRequestedEvent($withdrawal));

        return response()->json([
            'status'     => true,
            'message'    => 'Withdrawal request submitted.',
            'withdrawal' => $withdrawal,
        ], 201);
    }

    public function withdrawals(Request $request): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }
        $status = $request->get('status', 'all');

        $query = CarPoolWithdrawalRequest::where('driver_id', $driver->id)
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at');

        return response()->json([
            'status'      => true,
            'withdrawals' => $query->paginate((int) $request->get('limit', 15)),
        ]);
    }
}
