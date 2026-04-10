<?php

namespace App\Services;

use App\Traits\FileManagerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TowProviderService
{
    use FileManagerTrait;

    public function getAddData(Request $request): array
    {
        $storage = config('filesystems.disks.default') ?? 'public';

        return [
            'user_id' => $request['user_id'],
            'company_name' => $request['company_name'],
            'business_license' => $this->upload('provider/licenses/', 'pdf', $request->file('business_license')),
            'insurance_info' => $this->upload('provider/insurance/', 'pdf', $request->file('insurance_info')),
            'service_area' => $request['service_area'],
            'max_simultaneous_trips' => $request['max_simultaneous_trips'] ?? 3,
            'current_trips_count' => 0,
            'rating' => 0,
            'total_completed_trips' => 0,
            'status' => 'offline',
        ];
    }

    public function getUpdateData(Request $request, object $provider): array
    {
        $storage = config('filesystems.disks.default') ?? 'public';
        
        $data = [
            'company_name' => $request['company_name'],
            'service_area' => $request['service_area'],
            'max_simultaneous_trips' => $request['max_simultaneous_trips'],
        ];

        // Handle license file update
        if ($request->hasFile('business_license')) {
            $this->delete('provider/licenses/' . $provider->business_license);
            $data['business_license'] = $this->upload('provider/licenses/', 'pdf', $request->file('business_license'));
        }

        // Handle insurance file update
        if ($request->hasFile('insurance_info')) {
            $this->delete('provider/insurance/' . $provider->insurance_info);
            $data['insurance_info'] = $this->upload('provider/insurance/', 'pdf', $request->file('insurance_info'));
        }

        return $data;
    }

    
    public function getStatistics(): array
    {
        return [
            'total' => 0,
            'available' => 0,
            'busy' => 0,
            'offline' => 0,
            'on_break' => 0,
            'top_rated' => 0,
            'total_trips' => 0,
            'newCounter' => 0,
        ];
    }

    public function getStatusBadge(string $status): string
    {
        $badges = [
            'available' => 'success',
            'busy' => 'warning',
            'offline' => 'secondary',
            'on_break' => 'info'
        ];

        return $badges[$status] ?? 'primary';
    }

    public function calculateDistance(
        float $lat1, 
        float $lon1, 
        float $lat2, 
        float $lon2, 
        string $unit = 'km'
    ): float {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        if ($unit == 'km') {
            return round($miles * 1.609344, 1);
        }
        
        return round($miles, 1);
    }

    public function getAvailabilitySlots(int $maxTrips, int $currentTrips): int
    {
        return $maxTrips - $currentTrips;
    }

    public function getAvgResponseTime($provider): float
{
    // Calculate average response time from active_trips
    return 0; // Placeholder
}

public function getTotalEarnings($provider): float
{
    // Calculate total earnings from completed trips
    return 0; // Placeholder
}

    public function isAvailable(string $status, int $currentTrips, int $maxTrips): bool
    {
        return $status === 'available' && $currentTrips < $maxTrips;
    }

    public function updateRating(float $currentRating, int $totalTrips, float $newRating): float
    {
        if ($totalTrips == 0) {
            return $newRating;
        }

        return ($currentRating * $totalTrips + $newRating) / ($totalTrips + 1);
    }

    public function getProviderStatsHtml(object $provider): string
    {
        $html = '<div class="provider-stats">';
        $html .= '<div class="rating">⭐ ' . number_format($provider->rating, 1) . '</div>';
        $html .= '<div class="trips">🚗 ' . $provider->total_completed_trips . ' ' . translate('trips') . '</div>';
        $html .= '<div class="availability">';
        $html .= '<span class="badge badge-' . $this->getStatusBadge($provider->status) . '">';
        $html .= translate($provider->status);
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function getSelectServiceAreaOptions(array $areas, string $selected = null): string
    {
        $output = '<option value="" disabled selected>' . translate('select_service_area') . '</option>';
        
        foreach ($areas as $area) {
            $selectedAttr = ($selected && $area == $selected) ? 'selected' : '';
            $output .= '<option value="' . $area . '" ' . $selectedAttr . '>' . translate($area) . '</option>';
        }
        
        return $output;
    }

    public function deleteFiles(object $provider): bool
    {
        if ($provider->business_license) {
            $this->delete('provider/licenses/' . $provider->business_license);
        }
        
        if ($provider->insurance_info) {
            $this->delete('provider/insurance/' . $provider->insurance_info);
        }
        
        return true;
    }
}