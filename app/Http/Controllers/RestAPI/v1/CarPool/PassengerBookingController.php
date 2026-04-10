<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolReview;
use App\Models\CarPoolRoute;
use App\Repositories\CarPoolBookingRepository;
use App\Services\CarPoolBookingService;
use App\Services\CarPoolPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PassengerBookingController extends Controller
{
    public function __construct(
        private readonly CarPoolBookingRepository $bookingRepo,
        private readonly CarPoolBookingService    $bookingService,
        private readonly CarPoolPaymentService    $paymentService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'route_id'      => 'required|integer|exists:carpool_routes,id',
            'seat_count'    => 'required|integer|min:1|max:10',
            'pickup_name'   => 'nullable|string|max:255',
            'pickup_lat'    => 'nullable|numeric|between:-90,90',
            'pickup_lng'    => 'nullable|numeric|between:-180,180',
            'drop_name'     => 'nullable|string|max:255',
            'drop_lat'      => 'nullable|numeric|between:-90,90',
            'drop_lng'      => 'nullable|numeric|between:-180,180',
            'payment_method'=> 'required|in:wallet,online',
            'passengers'    => 'nullable|array',
            'passengers.*.name'   => 'required_with:passengers|string|max:100',
            'passengers.*.phone'  => 'nullable|string|max:20',
            'passengers.*.gender' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $passenger = $request->user('api');
        $route     = CarPoolRoute::find($request->route_id);

        if (!in_array($route->route_status, ['open', 'full'])) {
            return response()->json(['status' => false, 'message' => 'Route is no longer accepting bookings.'], 422);
        }

        if ($route->available_seats < $request->seat_count) {
            return response()->json(['status' => false, 'message' => 'Not enough seats available.'], 422);
        }

        try {
            $booking = $this->bookingService->createBooking($route, array_merge(
                $validator->validated(),
                ['passenger_id' => $passenger->id]
            ));
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        // If wallet payment, charge immediately.
        if ($request->payment_method === 'wallet') {
            try {
                $this->paymentService->chargeWallet($booking, $passenger);
            } catch (\RuntimeException $e) {
                // Roll back seat reservation on payment failure.
                $this->bookingService->cancel($booking, 'system', 'Payment failed: ' . $e->getMessage());
                return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Booking confirmed.',
                'booking' => $booking->fresh(['route', 'passengers', 'transaction']),
            ], 201);
        }

        // Online payment: return pending booking with transaction details.
        $transaction = $this->paymentService->chargeOnline($booking);

        return response()->json([
            'status'      => true,
            'message'     => 'Booking created. Complete payment to confirm.',
            'booking'     => $booking->fresh(['route', 'passengers']),
            'transaction' => $transaction,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $passenger = $request->user('api');
        $status    = $request->get('status', 'all');

        $bookings = $this->bookingRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            filters: array_filter(['passenger_id' => $passenger->id, 'status' => $status]),
            relations: ['route.driver', 'passengers'],
            dataLimit: (int) $request->get('limit', 15)
        );

        return response()->json(['status' => true, 'bookings' => $bookings]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $passenger = $request->user('api');
        $booking   = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $passenger->id],
            ['route.driver', 'passengers', 'transaction', 'review']
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json(['status' => true, 'booking' => $booking]);
    }

    public function pay(Request $request, int $id): JsonResponse
    {
        $passenger = $request->user('api');
        $booking   = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $passenger->id],
            ['route']
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json(['status' => false, 'message' => 'Already paid.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'gateway_reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $this->paymentService->handlePaymentSuccess($booking, $request->gateway_reference);

        return response()->json([
            'status'  => true,
            'message' => 'Payment confirmed. Booking is now confirmed.',
            'booking' => $booking->fresh(),
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $passenger = $request->user('api');
        $booking   = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $passenger->id],
            ['route']
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        try {
            $this->bookingService->cancel($booking, 'passenger', $request->get('reason'));

            if ($booking->payment_status === 'paid') {
                $this->paymentService->refund($booking);
            }
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => true, 'message' => 'Booking cancelled.']);
    }

    public function review(Request $request, int $id): JsonResponse
    {
        $passenger = $request->user('api');
        $booking   = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $passenger->id],
            ['route']
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->status !== 'completed') {
            return response()->json(['status' => false, 'message' => 'You can only review completed rides.'], 422);
        }

        if (CarPoolReview::where('booking_id', $id)->exists()) {
            return response()->json(['status' => false, 'message' => 'Review already submitted.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating'  => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $review = CarPoolReview::create([
            'booking_id'   => $booking->id,
            'route_id'     => $booking->route_id,
            'driver_id'    => $booking->route->driver_id,
            'passenger_id' => $passenger->id,
            'rating'       => $request->rating,
            'comment'      => $request->comment,
        ]);

        // Recalculate driver average rating.
        $avgRating = CarPoolReview::where('driver_id', $booking->route->driver_id)
            ->where('status', 'published')
            ->avg('rating');

        $booking->route->driver->update(['rating' => round($avgRating, 2)]);

        return response()->json(['status' => true, 'message' => 'Review submitted.', 'review' => $review], 201);
    }
}
