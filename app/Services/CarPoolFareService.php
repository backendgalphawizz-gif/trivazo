<?php

namespace App\Services;

use App\Models\CarPoolRoute;

class CarPoolFareService
{
    /**
     * Estimate total fare for a given seat count on a route.
     */
    public function estimate(CarPoolRoute $route, int $seatCount): float
    {
        return round($route->price_per_seat * $seatCount, 2);
    }

    /**
     * Split the fare total into admin commission and driver amount.
     * Returns ['admin_commission' => float, 'driver_amount' => float].
     */
    public function splitFare(float $fareTotal): array
    {
        $commissionPct = (float) $this->getCommissionPercentage();
        $adminCommission = round($fareTotal * ($commissionPct / 100), 2);
        $driverAmount    = round($fareTotal - $adminCommission, 2);

        return [
            'admin_commission' => $adminCommission,
            'driver_amount'    => $driverAmount,
        ];
    }

    private function getCommissionPercentage(): float
    {
        // Allow override via business_settings if available.
        try {
            $setting = \DB::table('business_settings')
                ->where('key', 'carpool_commission_percentage')
                ->value('value');

            if ($setting !== null) {
                return (float) $setting;
            }
        } catch (\Throwable) {
            // Fall through to config default.
        }

        return (float) config('carpool.commission_percentage', 10);
    }
}
