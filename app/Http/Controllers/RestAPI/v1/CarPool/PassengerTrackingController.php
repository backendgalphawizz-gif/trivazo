<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolBooking;
use App\Models\CarPoolRoute;
use App\Models\CarPoolStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PassengerTrackingController extends Controller
{
    /**
     * GET /api/v1/carpool/passenger/track/{bookingId}
     *
     * Returns real-time tracking info for a confirmed/in-progress booking:
     * - Booking & route status
     * - Driver current location (from driver's last known lat/lng)
     * - Ride status timeline (status history)
     */
    public function track(Request $request, int $bookingId): JsonResponse
    {
        $user    = $request->user('api');
        $booking = CarPoolBooking::with(['route.driver', 'passengers'])
            ->where('id', $bookingId)
            ->where('passenger_id', $user->id)
            ->first();

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found.'], 404);
        }

        if (!in_array($booking->status, ['confirmed', 'departed', 'completed'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Tracking is only available for confirmed or active rides.',
                'booking_status' => $booking->status,
            ], 422);
        }

        $route  = $booking->route;
        $driver = $route?->driver;

        // Status history for this booking
        $history = CarPoolStatusHistory::where('target_type', 'booking')
            ->where('target_id', $booking->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($h) => [
                'from'       => $h->old_status,
                'to'         => $h->new_status,
                'at'         => $h->created_at?->toIso8601String(),
                'note'       => $h->note,
            ]);

        return response()->json([
            'status'  => true,
            'tracking' => [
                'booking_id'       => $booking->id,
                'booking_code'     => $booking->booking_code,
                'booking_status'   => $booking->status,
                'payment_status'   => $booking->payment_status,
                'seat_count'       => $booking->seat_count,
                'fare_total'       => $booking->fare_total,
                'pickup_name'      => $booking->pickup_name,
                'drop_name'        => $booking->drop_name,
                'confirmed_at'     => $booking->confirmed_at?->toIso8601String(),
                'departed_at'      => $booking->departed_at?->toIso8601String(),
                'completed_at'     => $booking->completed_at?->toIso8601String(),

                'route' => $route ? [
                    'id'               => $route->id,
                    'origin_name'      => $route->origin_name,
                    'origin_lat'       => $route->origin_lat,
                    'origin_lng'       => $route->origin_lng,
                    'destination_name' => $route->destination_name,
                    'destination_lat'  => $route->destination_lat,
                    'destination_lng'  => $route->destination_lng,
                    'departure_at'     => $route->departure_at?->toIso8601String(),
                    'route_status'     => $route->route_status,
                    'estimated_duration_min' => $route->estimated_duration_min,
                    'estimated_distance_km'  => $route->estimated_distance_km,
                ] : null,

                'driver' => $driver ? [
                    'id'              => $driver->id,
                    'name'            => $driver->name,
                    'phone'           => $driver->phone,  // shown only for active ride
                    'profile_image'   => $driver->profile_image
                        ? Storage::disk('public')->url($driver->profile_image) : null,
                    'vehicle_image'   => $driver->vehicle_image
                        ? Storage::disk('public')->url($driver->vehicle_image) : null,
                    'vehicle_type'    => $driver->vehicle_type,
                    'vehicle_number'  => $driver->vehicle_number,
                    'vehicle_model'   => $driver->vehicle_model,
                    'vehicle_color'   => $driver->vehicle_color,
                    'rating'          => $driver->rating,
                    // Real-time location (updated by driver app)
                    'current_lat'     => $driver->current_lat ?? null,
                    'current_lng'     => $driver->current_lng ?? null,
                    'last_location_at'=> $driver->last_location_at ?? null,
                ] : null,

                'status_timeline' => $history,
            ],
        ]);
    }

    /**
     * GET /api/v1/carpool/passenger/track/{bookingId}/driver-location
     *
     * Lightweight polling endpoint — returns only driver's current location.
     * Mobile app can call this every 10–15s without loading full tracking data.
     */
    public function driverLocation(Request $request, int $bookingId): JsonResponse
    {
        $user    = $request->user('api');
        $booking = CarPoolBooking::with('route.driver:id,current_lat,current_lng,last_location_at,is_online')
            ->where('id', $bookingId)
            ->where('passenger_id', $user->id)
            ->whereIn('status', ['confirmed', 'departed'])
            ->first();

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Active booking not found.'], 404);
        }

        $driver = $booking->route?->driver;

        return response()->json([
            'status' => true,
            'location' => [
                'driver_id'       => $driver?->id,
                'current_lat'     => $driver?->current_lat ?? null,
                'current_lng'     => $driver?->current_lng ?? null,
                'last_location_at'=> $driver?->last_location_at ?? null,
                'is_online'       => $driver?->is_online ?? 0,
                'booking_status'  => $booking->status,
            ],
        ]);
    }
}
