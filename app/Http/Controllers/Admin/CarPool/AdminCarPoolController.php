<?php

namespace App\Http\Controllers\Admin\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolDriver;
use App\Models\CarPoolRoute;
use App\Models\CarPoolBooking;
use App\Models\CarPoolWithdrawalRequest;
use App\Repositories\CarPoolDriverRepository;
use App\Repositories\CarPoolBookingRepository;
use App\Repositories\CarPoolRouteRepository;
use App\Services\CarPoolDriverWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCarPoolController extends Controller
{
    public function __construct(
        private readonly CarPoolDriverRepository    $driverRepo,
        private readonly CarPoolRouteRepository     $routeRepo,
        private readonly CarPoolBookingRepository   $bookingRepo,
        private readonly CarPoolDriverWalletService $walletService
    ) {}

    // ─── Drivers ────────────────────────────────────────────────────────────

    public function drivers(Request $request): JsonResponse
    {
        $drivers = $this->driverRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            searchValue: $request->get('search'),
            filters: array_filter(['status' => $request->get('status', 'all'), 'is_verified' => $request->has('is_verified') ? (bool) $request->is_verified : null]),
            relations: ['wallet'],
            dataLimit: (int) $request->get('limit', 15)
        );
        return response()->json(['status' => true, 'drivers' => $drivers]);
    }

    public function verifyDriver(Request $request, int $id): JsonResponse
    {
        $driver = CarPoolDriver::find($id);
        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver not found.'], 404);
        }
        $this->driverRepo->update($id, ['is_verified' => true]);
        return response()->json(['status' => true, 'message' => 'Driver verified.']);
    }

    public function updateDriverStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,suspended',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
        $this->driverRepo->update($id, ['status' => $request->status]);
        return response()->json(['status' => true, 'message' => 'Driver status updated.']);
    }

    // ─── Routes ─────────────────────────────────────────────────────────────

    public function routes(Request $request): JsonResponse
    {
        $routes = $this->routeRepo->getListWhere(
            orderBy: ['departure_at' => 'desc'],
            searchValue: $request->get('search'),
            filters: array_filter([
                'route_status' => $request->get('status', 'all'),
                'ride_type'    => $request->get('ride_type'),
                'date_from'    => $request->get('date_from'),
                'date_to'      => $request->get('date_to'),
            ]),
            relations: ['driver'],
            dataLimit: (int) $request->get('limit', 15)
        );
        return response()->json(['status' => true, 'routes' => $routes]);
    }

    // ─── Bookings ───────────────────────────────────────────────────────────

    public function bookings(Request $request): JsonResponse
    {
        $bookings = $this->bookingRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            searchValue: $request->get('search'),
            filters: array_filter([
                'status'         => $request->get('status', 'all'),
                'payment_status' => $request->get('payment_status', 'all'),
                'date_from'      => $request->get('date_from'),
                'date_to'        => $request->get('date_to'),
            ]),
            relations: ['route.driver', 'passenger'],
            dataLimit: (int) $request->get('limit', 15)
        );
        return response()->json(['status' => true, 'bookings' => $bookings]);
    }

    // ─── Commission report ───────────────────────────────────────────────────

    public function commissionReport(Request $request): JsonResponse
    {
        $query = \App\Models\CarPoolTransaction::where('transaction_type', 'booking_payment')
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        return response()->json([
            'status' => true,
            'summary' => [
                'total_bookings'   => $query->count(),
                'total_revenue'    => $query->sum('amount'),
                'total_commission' => $query->sum('admin_commission'),
                'total_driver_paid'=> $query->sum('driver_amount'),
            ],
        ]);
    }

    // ─── Withdrawal requests ─────────────────────────────────────────────────

    public function withdrawalRequests(Request $request): JsonResponse
    {
        $status = $request->get('status', 'pending');

        $requests = CarPoolWithdrawalRequest::with('driver')
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('limit', 15));

        return response()->json(['status' => true, 'withdrawals' => $requests]);
    }

    public function approveWithdrawal(Request $request, int $id): JsonResponse
    {
        $withdrawal = CarPoolWithdrawalRequest::find($id);
        if (!$withdrawal || $withdrawal->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Withdrawal request not found or already processed.'], 404);
        }

        try {
            $this->walletService->approveWithdrawal($withdrawal, $request->get('note'));
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        $withdrawal->driver->update(['fcm_token' => $withdrawal->driver->fcm_token]);
        (new \App\Services\CarPoolNotificationService())->notifyDriverWithdrawalApproved(
            $withdrawal->driver, $withdrawal->amount
        );

        return response()->json(['status' => true, 'message' => 'Withdrawal approved and paid.']);
    }

    public function rejectWithdrawal(Request $request, int $id): JsonResponse
    {
        $withdrawal = CarPoolWithdrawalRequest::find($id);
        if (!$withdrawal || $withdrawal->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Withdrawal request not found or already processed.'], 404);
        }

        $this->walletService->rejectWithdrawal($withdrawal, $request->get('note'));

        return response()->json(['status' => true, 'message' => 'Withdrawal rejected.']);
    }
}
