<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolBookingPassenger;
use App\Models\CarPoolReview;
use App\Models\CarPoolRoute;
use App\Models\CarPoolSavedPassenger;
use App\Repositories\CarPoolBookingRepository;
use App\Services\CarPoolBookingService;
use App\Services\CarPoolFareService;
use App\Services\CarPoolPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PassengerBookingController extends Controller
{
    public function __construct(
        private readonly CarPoolBookingRepository $bookingRepo,
        private readonly CarPoolBookingService    $bookingService,
        private readonly CarPoolPaymentService    $paymentService,
        private readonly CarPoolFareService       $fareService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $validator = Validator::make($request->all(), [
            'route_id'              => 'required|integer|exists:carpool_routes,id',
            'seat_count'            => 'required|integer|min:1|max:10',
            'amount'                => 'required|numeric|min:0',
            'tax_amount'            => 'required|numeric|min:0',
            'final_amount'          => 'required|numeric|min:0',
            'pickup_name'           => 'nullable|string|max:255',
            'pickup_lat'            => 'nullable|numeric|between:-90,90',
            'pickup_lng'            => 'nullable|numeric|between:-180,180',
            'drop_name'             => 'nullable|string|max:255',
            'drop_lat'              => 'nullable|numeric|between:-90,90',
            'drop_lng'              => 'nullable|numeric|between:-180,180',
            'saved_passenger_ids'   => 'required|array|min:1',
            'saved_passenger_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate saved_passenger_ids count matches seat_count
        if (count($request->saved_passenger_ids) !== (int) $request->seat_count) {
            return response()->json([
                'status'  => false,
                'message' => 'Number of saved_passenger_ids (' . count($request->saved_passenger_ids) . ') must equal seat_count (' . $request->seat_count . ').',
            ], 422);
        }

        // Fetch saved passengers — must belong to this user
        $savedPassengers = \App\Models\CarPoolSavedPassenger::whereIn('id', $request->saved_passenger_ids)
            ->where('user_id', $user->id)
            ->get();

        if ($savedPassengers->count() !== count($request->saved_passenger_ids)) {
            return response()->json([
                'status'  => false,
                'message' => 'One or more saved_passenger_ids are invalid or do not belong to you.',
            ], 422);
        }

        // Build passenger list with saved_passenger_id reference
        $passengerList = $savedPassengers->map(fn($p) => [
            'saved_passenger_id' => $p->id,
            'name'               => $p->name,
            'phone'              => $p->phone,
            'gender'             => $p->gender,
            'age'                => $p->age,
        ])->toArray();

        // ── Route checks ──────────────────────────────────────────────────
        $route = CarPoolRoute::with('driver')->find($request->route_id);

        if (!in_array($route->route_status, ['open', 'full'])) {
            return response()->json(['status' => false, 'message' => 'This route is no longer accepting bookings.'], 422);
        }

        if ($route->available_seats < $request->seat_count) {
            return response()->json([
                'status'  => false,
                'message' => 'Not enough seats. Only ' . $route->available_seats . ' seat(s) left.',
            ], 422);
        }

        // ── Fare — use client-provided values ────────────────────────────
        $fareSubtotal = (float) $request->amount;
        $taxAmount    = (float) $request->tax_amount;
        $grandTotal   = (float) $request->final_amount;

        // ── Create booking ────────────────────────────────────────────────
        try {
            $booking = $this->bookingService->createBooking($route, [
                'passenger_id'  => $user->id,
                'route_id'      => $route->id,
                'seat_count'    => $request->seat_count,
                'pickup_name'   => $request->pickup_name  ?? $route->origin_name,
                'pickup_lat'    => $request->pickup_lat   ?? $route->origin_lat,
                'pickup_lng'    => $request->pickup_lng   ?? $route->origin_lng,
                'drop_name'     => $request->drop_name    ?? $route->destination_name,
                'drop_lat'      => $request->drop_lat     ?? $route->destination_lat,
                'drop_lng'      => $request->drop_lng     ?? $route->destination_lng,
                'payment_method'=> null,
                'passengers'    => $passengerList,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        // Auto-confirm — payment on ride start; store client-provided fare components
        $booking->update([
            'status'       => \App\Enums\CarPoolBookingStatus::CONFIRMED,
            'confirmed_at' => now(),
            'fare_total'   => $fareSubtotal,
            'tax_amount'   => $taxAmount,
            'final_amount' => $grandTotal,
        ]);

        $booking->load(['route.driver', 'passengers']);
        $driver = $booking->route->driver;

        return response()->json([
            'status'  => true,
            'message' => 'Booking confirmed. Payment will be collected when the ride starts.',
            'booking' => [
                'id'           => $booking->id,
                'booking_code' => $booking->booking_code,
                'status'       => $booking->status,
                'user_id'      => $user->id,

                // ── Seat & Fare ──────────────────────────────────────────
                'seat_count'     => $booking->seat_count,
                'fare_per_seat'  => (float) $route->price_per_seat,
                'amount'         => $fareSubtotal,
                'tax_amount'     => $taxAmount,
                'final_amount'   => $grandTotal,
                'currency'       => $route->currency ?? 'INR',
                'payment_method' => null,
                'payment_status' => $booking->payment_status,   // unpaid

                // ── Pickup Location ──────────────────────────────────────
                'pickup' => [
                    'name'       => $booking->pickup_name,
                    'lat'        => (float) $booking->pickup_lat,
                    'lng'        => (float) $booking->pickup_lng,
                    'map_url'    => 'https://maps.google.com/?q=' . $booking->pickup_lat . ',' . $booking->pickup_lng,
                ],

                // ── Drop Location ────────────────────────────────────────
                'drop' => [
                    'name'       => $booking->drop_name,
                    'lat'        => (float) $booking->drop_lat,
                    'lng'        => (float) $booking->drop_lng,
                    'map_url'    => 'https://maps.google.com/?q=' . $booking->drop_lat . ',' . $booking->drop_lng,
                ],

                'confirmed_at' => $booking->confirmed_at,
                'created_at'   => $booking->created_at,

                // ── Route ────────────────────────────────────────────────
                'route' => [
                    'id'          => $route->id,
                    'ride_type'   => $route->ride_type,
                    'origin' => [
                        'name'    => $route->origin_name,
                        'lat'     => (float) $route->origin_lat,
                        'lng'     => (float) $route->origin_lng,
                        'map_url' => 'https://maps.google.com/?q=' . $route->origin_lat . ',' . $route->origin_lng,
                    ],
                    'destination' => [
                        'name'    => $route->destination_name,
                        'lat'     => (float) $route->destination_lat,
                        'lng'     => (float) $route->destination_lng,
                        'map_url' => 'https://maps.google.com/?q=' . $route->destination_lat . ',' . $route->destination_lng,
                    ],
                    'waypoints'      => $route->waypoints,
                    'departure_at'   => $route->departure_at,
                    'duration_min'   => $route->estimated_duration_min,
                    'distance_km'    => $route->estimated_distance_km,
                    'price_per_seat' => (float) $route->price_per_seat,
                    'available_seats'=> $route->fresh()->available_seats,
                    'note'           => $route->note,
                ],

                // ── Driver ───────────────────────────────────────────────
                'driver' => $driver ? [
                    'id'             => $driver->id,
                    'name'           => $driver->name,
                    'phone'          => $driver->phone,
                    'rating'         => (float) $driver->rating,
                    'profile_image'  => $driver->profile_image,
                    'vehicle' => [
                        'type'     => $driver->vehicle_type,
                        'number'   => $driver->vehicle_number,
                        'model'    => $driver->vehicle_model,
                        'color'    => $driver->vehicle_color,
                        'capacity' => $driver->vehicle_capacity,
                        'image'    => $driver->vehicle_image,
                    ],
                ] : null,

                // ── Passengers ───────────────────────────────────────────
                'passengers' => $booking->passengers->map(fn($p) => [
                    'id'                 => $p->id,
                    'saved_passenger_id' => $p->saved_passenger_id,
                    'name'               => $p->name,
                    'phone'              => $p->phone,
                    'gender'             => $p->gender,
                    'age'                => $p->age,
                ]),
            ],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user   = $request->user('api');
        $status = $request->get('status'); // optional filter

        $bookings = $this->bookingRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            filters: array_filter(['passenger_id' => $user->id] + ($status ? ['status' => $status] : [])),
            relations: ['route.driver', 'passengers'],
            dataLimit: (int) $request->get('limit', 15)
        );

        $collection = method_exists($bookings, 'getCollection') ? $bookings->getCollection() : collect($bookings);
        $items = $collection->map(fn($b) => $this->formatBooking($b));

        return response()->json([
            'status'   => true,
            'total'    => $items->count(),
            'bookings' => $items,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user    = $request->user('api');
        $booking = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $user->id],
            ['route.driver', 'passengers', 'transaction', 'review']
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'status'  => true,
            'booking' => $this->formatBooking($booking),
        ]);
    }

    /**
     * Format a booking into a consistent response shape.
     */
    private function formatBooking($booking): array
    {
        $route  = $booking->route;
        $driver = $route?->driver;

        return [
            'id'           => $booking->id,
            'booking_code' => $booking->booking_code,
            'status'       => $booking->status,
            'user_id'      => $booking->user_id,

            // ── Fare ─────────────────────────────────────────────────────
            'seat_count'     => $booking->seat_count,
            'fare_per_seat'  => (float) ($route?->price_per_seat ?? 0),
            'amount'         => (float) $booking->fare_total,
            'tax_amount'     => (float) $booking->tax_amount,
            'final_amount'   => (float) $booking->final_amount,
            'currency'       => $route?->currency ?? 'INR',
            'payment_method' => $booking->payment_method,
            'payment_status' => $booking->payment_status,

            // ── Pickup ───────────────────────────────────────────────────
            'pickup' => [
                'name'    => $booking->pickup_name,
                'lat'     => (float) $booking->pickup_lat,
                'lng'     => (float) $booking->pickup_lng,
                'map_url' => 'https://maps.google.com/?q=' . $booking->pickup_lat . ',' . $booking->pickup_lng,
            ],

            // ── Drop ─────────────────────────────────────────────────────
            'drop' => [
                'name'    => $booking->drop_name,
                'lat'     => (float) $booking->drop_lat,
                'lng'     => (float) $booking->drop_lng,
                'map_url' => 'https://maps.google.com/?q=' . $booking->drop_lat . ',' . $booking->drop_lng,
            ],

            // ── Timestamps ───────────────────────────────────────────────
            'confirmed_at'  => $booking->confirmed_at,
            'departed_at'   => $booking->departed_at,
            'completed_at'  => $booking->completed_at,
            'cancelled_at'  => $booking->cancelled_at,
            'created_at'    => $booking->created_at,

            // ── Cancellation ─────────────────────────────────────────────
            'cancelled_by'        => $booking->cancelled_by,
            'cancellation_reason' => $booking->cancellation_reason,

            // ── Route ────────────────────────────────────────────────────
            'route' => $route ? [
                'id'        => $route->id,
                'ride_type' => $route->ride_type,
                'origin' => [
                    'name'    => $route->origin_name,
                    'lat'     => (float) $route->origin_lat,
                    'lng'     => (float) $route->origin_lng,
                    'map_url' => 'https://maps.google.com/?q=' . $route->origin_lat . ',' . $route->origin_lng,
                ],
                'destination' => [
                    'name'    => $route->destination_name,
                    'lat'     => (float) $route->destination_lat,
                    'lng'     => (float) $route->destination_lng,
                    'map_url' => 'https://maps.google.com/?q=' . $route->destination_lat . ',' . $route->destination_lng,
                ],
                'waypoints'      => $route->waypoints,
                'departure_at'   => $route->departure_at,
                'duration_min'   => $route->estimated_duration_min,
                'distance_km'    => $route->estimated_distance_km,
                'price_per_seat' => (float) $route->price_per_seat,
                'available_seats'=> $route->available_seats,
                'note'           => $route->note,
            ] : null,

            // ── Driver ───────────────────────────────────────────────────
            'driver' => $driver ? [
                'id'            => $driver->id,
                'name'          => $driver->name,
                'phone'         => $driver->phone,
                'rating'        => (float) $driver->rating,
                'profile_image' => $driver->profile_image,
                'vehicle' => [
                    'type'     => $driver->vehicle_type,
                    'number'   => $driver->vehicle_number,
                    'model'    => $driver->vehicle_model,
                    'color'    => $driver->vehicle_color,
                    'capacity' => $driver->vehicle_capacity,
                    'image'    => $driver->vehicle_image,
                ],
            ] : null,

            // ── Passengers ───────────────────────────────────────────────
            'passengers' => $booking->passengers ? $booking->passengers->map(fn($p) => [
                'id'                 => $p->id,
                'saved_passenger_id' => $p->saved_passenger_id,
                'name'               => $p->name,
                'phone'              => $p->phone,
                'gender'             => $p->gender,
                'age'                => $p->age,
            ]) : [],
        ];
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

    /**
     * POST /api/v1/carpool/passenger/bookings/{id}/passengers
     * Add a co-passenger to an existing (pending/confirmed) booking.
     */
    public function addPassenger(Request $request, int $id): JsonResponse
    {
        $user    = $request->user('api');
        $booking = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $user->id],
            ['route', 'passengers']
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Passengers can only be added to pending or confirmed bookings.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:100',
            'phone'  => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'age'    => 'nullable|integer|min:1|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Ensure we do not exceed the booked seat count.
        if ($booking->passengers->count() >= $booking->seat_count) {
            return response()->json([
                'status'  => false,
                'message' => 'Cannot add more passengers than the booked seat count (' . $booking->seat_count . ').',
            ], 422);
        }

        $passenger = CarPoolBookingPassenger::create([
            'booking_id' => $booking->id,
            'name'       => $validator->validated()['name'],
            'phone'      => $validator->validated()['phone'] ?? null,
            'gender'     => $validator->validated()['gender'] ?? null,
            'age'        => $validator->validated()['age'] ?? null,
        ]);

        return response()->json([
            'status'    => true,
            'message'   => 'Passenger added successfully.',
            'passenger' => $passenger,
            'total'     => $booking->passengers()->count(),
        ], 201);
    }

    /**
     * GET /api/v1/carpool/passenger/bookings/{id}/passengers
     * List all passengers of a booking.
     */
    public function listPassengers(Request $request, int $id): JsonResponse
    {
        $user    = $request->user('api');
        $booking = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $user->id],
            ['passengers']
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'status'     => true,
            'total'      => $booking->passengers->count(),
            'passengers' => $booking->passengers,
        ]);
    }

    /**
     * PUT /api/v1/carpool/passenger/bookings/{id}/passengers/{passengerId}
     * Update a co-passenger on an existing booking.
     */
    public function updatePassenger(Request $request, int $id, int $passengerId): JsonResponse
    {
        $user    = $request->user('api');
        $booking = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $user->id]
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        $passenger = CarPoolBookingPassenger::where('id', $passengerId)
            ->where('booking_id', $booking->id)
            ->first();

        if (!$passenger) {
            return response()->json(['status' => false, 'message' => 'Passenger not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|required|string|max:100',
            'phone'  => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'age'    => 'nullable|integer|min:1|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $passenger->update($validator->validated());

        return response()->json([
            'status'    => true,
            'message'   => 'Passenger updated.',
            'passenger' => $passenger->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/carpool/passenger/bookings/{id}/passengers/{passengerId}
     * Remove a co-passenger from a booking.
     */
    public function removePassenger(Request $request, int $id, int $passengerId): JsonResponse
    {
        $user    = $request->user('api');
        $booking = $this->bookingRepo->getFirstWhere(
            ['id' => $id, 'passenger_id' => $user->id],
            ['passengers']
        );

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        if (!in_array($booking->status, ['pending_payment', 'pending', 'confirmed'])) {
            return response()->json(['status' => false, 'message' => 'Cannot modify passengers at this stage.'], 422);
        }

        $passenger = CarPoolBookingPassenger::where('id', $passengerId)
            ->where('booking_id', $booking->id)
            ->first();

        if (!$passenger) {
            return response()->json(['status' => false, 'message' => 'Passenger not found.'], 404);
        }

        $passenger->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Passenger removed.',
            'total'   => $booking->passengers()->count(),
        ]);
    }
}
