<?php

namespace App\Services;

use App\Enums\CarPoolRouteStatus;
use App\Models\CarPoolRoute;
use Illuminate\Support\Facades\DB;

class CarPoolSeatService
{
    /**
     * Reserve seats on a route inside a database transaction with a row lock.
     * Throws \RuntimeException if not enough seats are available.
     */
    public function reserveSeats(CarPoolRoute $route, int $seatCount): void
    {
        DB::transaction(function () use ($route, $seatCount) {
            /** @var CarPoolRoute $fresh */
            $fresh = CarPoolRoute::lockForUpdate()->find($route->id);

            if ($fresh->available_seats < $seatCount) {
                throw new \RuntimeException('Not enough seats available.');
            }

            $fresh->available_seats -= $seatCount;
            $fresh->route_status     = $fresh->available_seats <= 0
                ? CarPoolRouteStatus::FULL
                : $fresh->route_status;

            $fresh->save();

            // Sync the in-memory model so callers see the updated value.
            $route->available_seats = $fresh->available_seats;
            $route->route_status    = $fresh->route_status;
        });
    }

    /**
     * Release seats back to the route (on booking cancellation).
     */
    public function releaseSeats(CarPoolRoute $route, int $seatCount): void
    {
        DB::transaction(function () use ($route, $seatCount) {
            $fresh = CarPoolRoute::lockForUpdate()->find($route->id);

            // Only release if route is not yet departed/completed.
            if (in_array($fresh->route_status, [CarPoolRouteStatus::DEPARTED, CarPoolRouteStatus::COMPLETED, CarPoolRouteStatus::CANCELLED])) {
                return;
            }

            $fresh->available_seats = min($fresh->available_seats + $seatCount, $fresh->total_seats);
            // If seats become available again, reopen from full.
            if ($fresh->route_status === CarPoolRouteStatus::FULL && $fresh->available_seats > 0) {
                $fresh->route_status = CarPoolRouteStatus::OPEN;
            }

            $fresh->save();
        });
    }
}
