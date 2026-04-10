<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolRoute;
use App\Repositories\CarPoolRouteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PassengerRouteController extends Controller
{
    public function __construct(private readonly CarPoolRouteRepository $routeRepo) {}

    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_lat'       => 'required|numeric|between:-90,90',
            'origin_lng'       => 'required|numeric|between:-180,180',
            'destination_lat'  => 'required|numeric|between:-90,90',
            'destination_lng'  => 'required|numeric|between:-180,180',
            'date'             => 'required|date_format:Y-m-d',
            'seats'            => 'required|integer|min:1|max:10',
            'radius_km'        => 'nullable|numeric|min:0.5|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $routes = $this->routeRepo->searchRoutes(
            originLat: (float) $request->origin_lat,
            originLng: (float) $request->origin_lng,
            destLat:   (float) $request->destination_lat,
            destLng:   (float) $request->destination_lng,
            date:      $request->date,
            seats:     (int) $request->seats,
            radiusKm:  (float) ($request->radius_km ?? 5.0),
            dataLimit: (int) $request->get('limit', 15)
        );

        return response()->json(['status' => true, 'routes' => $routes]);
    }

    public function show(int $id): JsonResponse
    {
        $route = CarPoolRoute::with(['driver'])->find($id);

        if (!$route || !in_array($route->route_status, ['open', 'full'])) {
            return response()->json(['status' => false, 'message' => 'Route not found or no longer available.'], 404);
        }

        return response()->json([
            'status' => true,
            'route'  => [
                'id'                    => $route->id,
                'origin_name'           => $route->origin_name,
                'origin_lat'            => $route->origin_lat,
                'origin_lng'            => $route->origin_lng,
                'destination_name'      => $route->destination_name,
                'destination_lat'       => $route->destination_lat,
                'destination_lng'       => $route->destination_lng,
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
                'driver'                => $route->driver ? [
                    'id'           => $route->driver->id,
                    'name'         => $route->driver->name,
                    'rating'       => $route->driver->rating,
                    'vehicle_type' => $route->driver->vehicle_type,
                    'vehicle_number'=> $route->driver->vehicle_number,
                    'vehicle_color'=> $route->driver->vehicle_color,
                    'vehicle_model'=> $route->driver->vehicle_model,
                    'total_completed_rides' => $route->driver->total_completed_rides,
                ] : null,
            ],
        ]);
    }
}
