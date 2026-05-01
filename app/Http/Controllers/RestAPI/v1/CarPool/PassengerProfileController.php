<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolBooking;
use App\Models\CarPoolReview;
use App\Models\CarPoolRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PassengerProfileController extends Controller
{
    /**
     * GET /api/v1/carpool/passenger/profile
     * Returns the authenticated user's basic profile info.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user('api');

        return response()->json([
            'status'  => true,
            'profile' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'phone'  => $user->phone,
                'image'  => $user->image ?? null,
                'stats'  => [
                    'total_bookings'     => CarPoolBooking::where('passenger_id', $user->id)->count(),
                    'completed_rides'    => CarPoolBooking::where('passenger_id', $user->id)->where('status', 'completed')->count(),
                    'cancelled_rides'    => CarPoolBooking::where('passenger_id', $user->id)->where('status', 'cancelled')->count(),
                    'pending_bookings'   => CarPoolBooking::where('passenger_id', $user->id)->whereIn('status', ['pending_payment', 'confirmed'])->count(),
                    'reviews_given'      => CarPoolReview::where('passenger_id', $user->id)->count(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/carpool/passenger/bookings
     * Paginated list of the passenger's bookings with filters.
     */
    public function bookings(Request $request): JsonResponse
    {
        $user   = $request->user('api');
        $status = $request->get('status'); // pending_payment|confirmed|departed|completed|cancelled
        $limit  = (int) $request->get('limit', 15);

        $query = CarPoolBooking::with(['route.driver', 'passengers', 'transaction'])
            ->where('passenger_id', $user->id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at');

        $bookings = $query->paginate($limit);

        return response()->json([
            'status'   => true,
            'bookings' => $bookings->map(fn($b) => $this->formatBooking($b)),
            'meta'     => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/carpool/passenger/bookings/{id}
     * Single booking detail.
     */
    public function bookingDetail(Request $request, int $id): JsonResponse
    {
        $user    = $request->user('api');
        $booking = CarPoolBooking::with(['route.driver', 'passengers', 'transaction', 'review'])
            ->where('id', $id)
            ->where('passenger_id', $user->id)
            ->first();

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json(['status' => true, 'booking' => $this->formatBooking($booking, detailed: true)]);
    }

    /**
     * GET /api/v1/carpool/passenger/active
     * Returns the passenger's active (confirmed/departed) ride, if any.
     */
    public function activeRide(Request $request): JsonResponse
    {
        $user    = $request->user('api');
        $booking = CarPoolBooking::with(['route.driver', 'passengers'])
            ->where('passenger_id', $user->id)
            ->whereIn('status', ['confirmed', 'departed'])
            ->latest('confirmed_at')
            ->first();

        if (!$booking) {
            return response()->json(['status' => true, 'active_ride' => null, 'message' => 'No active ride.']);
        }

        return response()->json([
            'status'      => true,
            'active_ride' => $this->formatBooking($booking, detailed: true),
        ]);
    }

    /**
     * GET /api/v1/carpool/passenger/reviews
     * Reviews written by the passenger.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $user    = $request->user('api');
        $reviews = CarPoolReview::with(['route'])
            ->where('passenger_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('limit', 15));

        return response()->json([
            'status'  => true,
            'reviews' => $reviews->map(fn($r) => [
                'id'         => $r->id,
                'booking_id' => $r->booking_id,
                'rating'     => $r->rating,
                'comment'    => $r->comment,
                'created_at' => $r->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/carpool/passenger/driver/{driverId}
     * Public driver profile — ratings, vehicle info, total rides.
     */
    public function driverProfile(int $driverId): JsonResponse
    {
        $driver = \App\Models\CarPoolDriver::find($driverId);

        if (!$driver || $driver->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Driver not found.'], 404);
        }

        $reviews = CarPoolReview::with('passenger:id,name,image')
            ->where('driver_id', $driverId)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => true,
            'driver' => [
                'id'                    => $driver->id,
                'name'                  => $driver->name,
                'profile_image'         => $driver->profile_image
                    ? Storage::disk('public')->url($driver->profile_image) : null,
                'vehicle_image'         => $driver->vehicle_image
                    ? Storage::disk('public')->url($driver->vehicle_image) : null,
                'vehicle_type'          => $driver->vehicle_type,
                'vehicle_number'        => $driver->vehicle_number,
                'vehicle_model'         => $driver->vehicle_model,
                'vehicle_color'         => $driver->vehicle_color,
                'vehicle_capacity'      => $driver->vehicle_capacity,
                'rating'                => $driver->rating,
                'total_completed_rides' => $driver->total_completed_rides,
                'reviews'               => $reviews->map(fn($r) => [
                    'passenger_name' => $r->passenger->name ?? 'Anonymous',
                    'passenger_image'=> $r->passenger->image ?? null,
                    'rating'         => $r->rating,
                    'comment'        => $r->comment,
                    'date'           => $r->created_at?->toDateString(),
                ]),
            ],
        ]);
    }

    /* ── Private helpers ── */

    private function formatBooking(CarPoolBooking $b, bool $detailed = false): array
    {
        $data = [
            'id'             => $b->id,
            'booking_code'   => $b->booking_code,
            'status'         => $b->status,
            'payment_status' => $b->payment_status,
            'payment_method' => $b->payment_method,
            'seat_count'     => $b->seat_count,
            'fare_total'     => $b->fare_total,
            'pickup_name'    => $b->pickup_name,
            'drop_name'      => $b->drop_name,
            'created_at'     => $b->created_at?->toIso8601String(),
        ];

        if ($b->relationLoaded('route') && $b->route) {
            $data['route'] = [
                'id'               => $b->route->id,
                'origin_name'      => $b->route->origin_name,
                'destination_name' => $b->route->destination_name,
                'departure_at'     => $b->route->departure_at?->toIso8601String(),
                'currency'         => $b->route->currency,
                'price_per_seat'   => $b->route->price_per_seat,
                'driver'           => $b->route->driver ? [
                    'id'             => $b->route->driver->id,
                    'name'           => $b->route->driver->name,
                    'phone'          => $detailed ? $b->route->driver->phone : null,
                    'profile_image'  => $b->route->driver->profile_image
                        ? Storage::disk('public')->url($b->route->driver->profile_image) : null,
                    'vehicle_image'  => $b->route->driver->vehicle_image
                        ? Storage::disk('public')->url($b->route->driver->vehicle_image) : null,
                    'vehicle_number' => $b->route->driver->vehicle_number,
                    'vehicle_model'  => $b->route->driver->vehicle_model,
                    'vehicle_color'  => $b->route->driver->vehicle_color,
                    'rating'         => $b->route->driver->rating,
                ] : null,
            ];
        }

        if ($detailed) {
            $data['passengers']    = $b->passengers ?? [];
            $data['confirmed_at']  = $b->confirmed_at?->toIso8601String();
            $data['departed_at']   = $b->departed_at?->toIso8601String();
            $data['completed_at']  = $b->completed_at?->toIso8601String();
            $data['cancelled_at']  = $b->cancelled_at?->toIso8601String();
            $data['review']        = $b->review ?? null;
        }

        return $data;
    }
}
