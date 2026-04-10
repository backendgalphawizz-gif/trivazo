<?php

namespace App\Services;

use Illuminate\Http\Request;

class TripTrackingService
{
    public function getAddData(Request $request): array
    {
        return [
            'trip_id' => $request['trip_id'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'speed' => $request->get('speed', 0),
            'heading' => $request->get('heading', 0),
            'recorded_at' => now(),
        ];
    }

    public function calculateDistanceTraveled(object $trackingPoints): float
    {
        $distance = 0;
        $previousPoint = null;

        foreach ($trackingPoints as $point) {
            if ($previousPoint) {
                $distance += $this->haversineDistance(
                    $previousPoint->latitude,
                    $previousPoint->longitude,
                    $point->latitude,
                    $point->longitude
                );
            }
            $previousPoint = $point;
        }

        return round($distance, 2);
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function calculateAverageSpeed(object $trackingPoints): float
    {
        if ($trackingPoints->count() < 2) {
            return 0;
        }

        $totalSpeed = 0;
        $count = 0;

        foreach ($trackingPoints as $point) {
            if ($point->speed > 0) {
                $totalSpeed += $point->speed;
                $count++;
            }
        }

        return $count > 0 ? round($totalSpeed / $count, 1) : 0;
    }

    public function getPathForMap(object $trackingPoints): array
    {
        $path = [];
        
        foreach ($trackingPoints as $point) {
            $path[] = [
                'lat' => $point->latitude,
                'lng' => $point->longitude,
                'timestamp' => $point->recorded_at->timestamp,
            ];
        }

        return $path;
    }

    public function getLastLocation(object $trackingPoints): ?array
    {
        $lastPoint = $trackingPoints->last();
        
        if (!$lastPoint) {
            return null;
        }

        return [
            'lat' => $lastPoint->latitude,
            'lng' => $lastPoint->longitude,
            'speed' => $lastPoint->speed,
            'heading' => $lastPoint->heading,
            'time' => $lastPoint->recorded_at->format('H:i:s'),
        ];
    }

    public function isWithinGeofence(float $lat, float $lng, float $targetLat, float $targetLng, float $radius = 100): bool
    {
        $distance = $this->haversineDistance($lat, $lng, $targetLat, $targetLng) * 1000; // Convert to meters
        return $distance <= $radius;
    }

    public function getEtaFromLocation(float $currentLat, float $currentLng, float $targetLat, float $targetLng, float $averageSpeed = 30): int
    {
        $distance = $this->haversineDistance($currentLat, $currentLng, $targetLat, $targetLng);
        return round(($distance / $averageSpeed) * 60); // minutes
    }
}