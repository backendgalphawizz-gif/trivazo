<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\RestAPI\v1\CarPool\Concerns\ResolvesCarpoolDriverFromUser;
use App\Enums\CarPoolRouteStatus;
use App\Events\CarPool\CarPoolRideCompletedEvent;
use App\Http\Controllers\Controller;
use App\Models\CarPoolRoute;
use App\Models\CarPoolStatusHistory;
use App\Repositories\CarPoolRouteRepository;
use App\Services\CarPoolBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverRouteController extends Controller
{
    use ResolvesCarpoolDriverFromUser;

    public function __construct(
        private readonly CarPoolRouteRepository $routeRepo,
        private readonly CarPoolBookingService $bookingService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }

        if (!$driver->is_verified) {
            return response()->json(['status' => false, 'message' => 'Account not yet verified.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'origin_name'       => 'required|string|max:255',
            'origin_lat'        => 'required|numeric|between:-90,90',
            'origin_lng'        => 'required|numeric|between:-180,180',
            'destination_name'  => 'required|string|max:255',
            'destination_lat'   => 'required|numeric|between:-90,90',
            'destination_lng'   => 'required|numeric|between:-180,180',
            'waypoints'         => 'nullable|array',
            'ride_type'         => 'required|in:instant,scheduled',
            'departure_at'      => 'required|date|after:now',
            'total_seats'       => 'required|integer|min:1|max:' . $driver->vehicle_capacity,
            'price_per_seat'    => 'required|numeric|min:0',
            'currency'          => 'nullable|string|max:10',
            'note'              => 'nullable|string|max:500',
            'estimated_duration_min' => 'nullable|integer|min:1',
            'estimated_distance_km'  => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['driver_id']       = $driver->id;
        $data['available_seats'] = $data['total_seats'];
        $data['route_status']    = CarPoolRouteStatus::OPEN;

        $route = $this->routeRepo->add($data);

        return response()->json([
            'status'  => true,
            'message' => 'Route created successfully.',
            'route'   => $this->formatRoute($route),
        ], 201);
    }

    public function myRoutes(Request $request): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }
        $status = $request->get('status', 'all');

        $routes = $this->routeRepo->getListWhere(
            orderBy: ['departure_at' => 'desc'],
            filters: array_filter(['driver_id' => $driver->id, 'route_status' => $status]),
            relations: ['bookings'],
            dataLimit: (int) $request->get('limit', 15)
        );

        return response()->json(['status' => true, 'routes' => $routes]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }
        $route  = $this->routeRepo->getFirstWhere(['id' => $id, 'driver_id' => $driver->id], ['bookings.passenger', 'bookings.passengers']);

        if (!$route) {
            return response()->json(['status' => false, 'message' => 'Route not found.'], 404);
        }

        return response()->json(['status' => true, 'route' => $this->formatRoute($route)]);
    }

    public function depart(Request $request, int $id): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }
        $route  = $this->routeRepo->getFirstWhere(['id' => $id, 'driver_id' => $driver->id]);

        if (!$route) {
            return response()->json(['status' => false, 'message' => 'Route not found.'], 404);
        }

        if (!in_array($route->route_status, [CarPoolRouteStatus::OPEN, CarPoolRouteStatus::FULL])) {
            return response()->json(['status' => false, 'message' => 'Route cannot be departed at this stage.'], 422);
        }

        $old = $route->route_status;
        $this->routeRepo->update($route->id, ['route_status' => CarPoolRouteStatus::DEPARTED]);
        $this->bookingService->transitionBookingsForRoute($route, 'departed', 'driver');

        CarPoolStatusHistory::create([
            'target_type' => 'route', 'target_id' => $route->id,
            'old_status' => $old, 'new_status' => CarPoolRouteStatus::DEPARTED,
            'actor_type' => 'driver', 'actor_id' => $driver->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Ride departed.']);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }
        $route  = $this->routeRepo->getFirstWhere(['id' => $id, 'driver_id' => $driver->id], ['bookings']);

        if (!$route) {
            return response()->json(['status' => false, 'message' => 'Route not found.'], 404);
        }

        if ($route->route_status !== CarPoolRouteStatus::DEPARTED) {
            return response()->json(['status' => false, 'message' => 'Route must be in departed status to complete.'], 422);
        }

        $this->routeRepo->update($route->id, ['route_status' => CarPoolRouteStatus::COMPLETED]);
        $this->bookingService->transitionBookingsForRoute($route, 'completed', 'driver');

        CarPoolStatusHistory::create([
            'target_type' => 'route', 'target_id' => $route->id,
            'old_status' => CarPoolRouteStatus::DEPARTED, 'new_status' => CarPoolRouteStatus::COMPLETED,
            'actor_type' => 'driver', 'actor_id' => $driver->id,
        ]);

        // Dispatch completion event for each completed booking.
        $route->bookings()->where('status', 'completed')->each(function ($booking) {
            event(new CarPoolRideCompletedEvent($booking->load(['route', 'passenger'])));
        });

        // Increment driver completed rides counter.
        $driver->increment('total_completed_rides');

        return response()->json(['status' => true, 'message' => 'Ride completed. Earnings settled.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }
        $route  = $this->routeRepo->getFirstWhere(['id' => $id, 'driver_id' => $driver->id]);

        if (!$route) {
            return response()->json(['status' => false, 'message' => 'Route not found.'], 404);
        }

        $hasConfirmed = $route->confirmedBookings()->exists();
        if ($hasConfirmed) {
            return response()->json(['status' => false, 'message' => 'Cannot cancel a route with confirmed bookings.'], 422);
        }

        $this->routeRepo->update($route->id, ['route_status' => CarPoolRouteStatus::CANCELLED]);

        return response()->json(['status' => true, 'message' => 'Route cancelled.']);
    }

    /**
     * POST /api/v1/carpool/driver/location
     * Driver pushes their current GPS position (called every ~10s from driver app).
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }
        $driver->update([
            'current_lat'       => $request->lat,
            'current_lng'       => $request->lng,
            'last_location_at'  => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Location updated.']);
    }

    private function formatRoute(CarPoolRoute $route): array
    {
        return [
            'id'                    => $route->id,
            'origin_name'           => $route->origin_name,
            'origin_lat'            => $route->origin_lat,
            'origin_lng'            => $route->origin_lng,
            'destination_name'      => $route->destination_name,
            'destination_lat'       => $route->destination_lat,
            'destination_lng'       => $route->destination_lng,
            'waypoints'             => $route->waypoints,
            'ride_type'             => $route->ride_type,
            'departure_at'          => $route->departure_at,
            'estimated_duration_min'=> $route->estimated_duration_min,
            'estimated_distance_km' => $route->estimated_distance_km,
            'total_seats'           => $route->total_seats,
            'available_seats'       => $route->available_seats,
            'price_per_seat'        => $route->price_per_seat,
            'currency'              => $route->currency,
            'route_status'          => $route->route_status,
            'note'                  => $route->note,
            'bookings_count'        => $route->bookings ? $route->bookings->count() : null,
            'bookings'              => $route->relationLoaded('bookings') ? $route->bookings : null,
            'created_at'            => $route->created_at,
        ];
    }
}
