<?php

namespace App\Services;

use App\Traits\FileManagerTrait;
use Illuminate\Http\Request;

class ActiveTripService
{
    use FileManagerTrait;

    public function getAssignData(Request $request, int $dispatcherId): array
    {
        return [
            'request_id' => $request['request_id'],
            'provider_id' => $request['provider_id'],
            'dispatcher_id' => $dispatcherId,
            'current_status' => 'assigned',
            'estimated_arrival_minutes' => $this->calculateEstimatedArrival($request),
            'distance_estimate' => $request->get('distance_estimate'),
            'created_at' => now(),
        ];
    }

    public function getReassignData(Request $request, object $activeTrip): array
    {
        return [
            'provider_id' => $request['new_provider_id'],
            'current_status' => 'assigned',
            'cancellation_reason' => $request['reassign_reason'],
            'updated_at' => now(),
        ];
    }

    public function getTimeFieldForStatus(string $status): ?string
    {
        $timeFields = [
            'accepted' => 'acceptance_time',
            'en_route' => 'en_route_time',
            'arrived' => 'arrival_time',
            'in_progress' => 'start_time',
            'completed' => 'completion_time',
        ];

        return $timeFields[$status] ?? null;
    }

    public function calculateEstimatedArrival(Request $request): int
    {
        // Simple calculation based on distance and average speed
        $distance = $request->get('distance_estimate', 5); // km
        $averageSpeed = 30; // km/h
        
        return round(($distance / $averageSpeed) * 60); // minutes
    }

    public function getProgressPercentage(string $status): int
    {
        $progressMap = [
            'assigned' => 10,
            'accepted' => 25,
            'en_route' => 50,
            'arrived' => 75,
            'in_progress' => 90,
            'completed' => 100
        ];

        return $progressMap[$status] ?? 0;
    }

    public function getStatusBadge(string $status): string
    {
        $badges = [
            'assigned' => 'secondary',
            'accepted' => 'info',
            'en_route' => 'primary',
            'arrived' => 'warning',
            'in_progress' => 'success',
            'completed' => 'dark'
        ];

        return $badges[$status] ?? 'light';
    }

    public function formatTrackingData(object $trackingLocations): array
    {
        $formattedData = [];
        
        foreach ($trackingLocations as $location) {
            $formattedData[] = [
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'speed' => $location->speed,
                'heading' => $location->heading,
                'time' => $location->recorded_at->format('H:i:s'),
                'timestamp' => $location->recorded_at->timestamp,
            ];
        }

        return $formattedData;
    }

    public function getDurationString(?\DateTime $startTime, ?\DateTime $endTime = null): string
    {
        if (!$startTime) {
            return '--';
        }

        $end = $endTime ?? now();
        $minutes = $startTime->diffInMinutes($end);

        if ($minutes < 60) {
            return $minutes . ' ' . translate('minutes');
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . 'h ' . $remainingMinutes . 'm';
    }

    public function getSelectProviderOptions(object $providers, int $currentProviderId = null): string
    {
        $output = '<option value="" disabled selected>' . translate('select_provider') . '</option>';
        
        foreach ($providers as $provider) {
            // Skip current provider if we're reassigning
            if ($currentProviderId && $provider->id == $currentProviderId) {
                continue;
            }
            
            $selected = ($currentProviderId && $provider->id == $currentProviderId) ? 'selected' : '';
            $disabled = !$provider->is_available ? 'disabled' : '';
            $availabilityText = $provider->is_available ? '' : ' (' . translate('not_available') . ')';
            
            $output .= '<option value="' . $provider->id . '" ' . $selected . ' ' . $disabled . '>' . 
                      $provider->company_name . ' - ' . $provider->owner_name . 
                      ' (⭐ ' . $provider->rating . ')' . $availabilityText .
                      '</option>';
        }
        
        return $output;
    }

    public function getStatistics(): array
{
    // You can implement this based on your needs
    // For now, returning placeholder data
    return [
        'total' => 0,
        'assigned' => 0,
        'accepted' => 0,
        'en_route' => 0,
        'arrived' => 0,
        'in_progress' => 0,
    ];
}
}