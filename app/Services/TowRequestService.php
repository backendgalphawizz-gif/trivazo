<?php

namespace App\Services;

use App\Traits\FileManagerTrait;
use Illuminate\Http\Request;

class TowRequestService
{
    use FileManagerTrait;

    public function getStatistics(array $filters): array
    {
        return [
            'total' => $this->getTotalCount($filters),
            'pending' => $this->getStatusCount('pending', $filters),
            'assigned' => $this->getStatusCount('assigned', $filters),
            'in_progress' => $this->getInProgressCount($filters),
            'completed' => $this->getStatusCount('completed', $filters),
            'cancelled' => $this->getStatusCount('cancelled', $filters),
            'emergency' => $this->getPriorityCount('emergency', $filters),
        ];
    }

    private function getTotalCount(array $filters): int
    {
        // This would typically call repository, but service doesn't have repo access
        // Statistics would be calculated in controller and passed to service
        return 0;
    }

    private function getStatusCount(string $status, array $filters): int
    {
        return 0; // Placeholder - actual implementation in controller
    }

    private function getInProgressCount(array $filters): int
    {
        return 0; // Placeholder
    }

    private function getPriorityCount(string $priority, array $filters): int
    {
        return 0; // Placeholder
    }

    public function getStatusUpdateData(Request $request, object $towRequest): array
    {
        $data = [
            'status' => $request['status'],
            'updated_at' => now(),
        ];

        // Add timestamps based on status
        switch ($request['status']) {
            case 'assigned':
                // No specific timestamp for request level
                break;
            case 'accepted':
                // Request accepted
                break;
            case 'en_route':
                // Provider on the way
                break;
            case 'arrived':
                // Provider arrived at location
                break;
            case 'in_progress':
                // Service started
                break;
            case 'completed':
                $data['final_price'] = $request->get('final_price', $towRequest->estimated_price);
                break;
            case 'cancelled':
                $data['cancellation_reason'] = $request['cancellation_reason'];
                break;
        }

        return $data;
    }

    public function getServiceTypeBadge(string $serviceType): string
    {
        $badges = [
            'emergency' => 'danger',
            'scheduled' => 'info',
            'battery_jump' => 'warning',
            'flat_tire' => 'secondary',
            'fuel_delivery' => 'success'
        ];

        return $badges[$serviceType] ?? 'primary';
    }

    public function getPriorityBadge(string $priority): string
    {
        $badges = [
            'low' => 'success',
            'normal' => 'info',
            'high' => 'warning',
            'emergency' => 'danger'
        ];

        return $badges[$priority] ?? 'primary';
    }

    public function getStatusBadge(string $status): string
    {
        $badges = [
            'pending' => 'secondary',
            'assigned' => 'info',
            'accepted' => 'primary',
            'en_route' => 'warning',
            'arrived' => 'info',
            'in_progress' => 'success',
            'completed' => 'dark',
            'cancelled' => 'danger'
        ];

        return $badges[$status] ?? 'light';
    }

    public function formatWaitingTime(\DateTime $createdAt): string
    {
        $minutes = now()->diffInMinutes($createdAt);
        
        if ($minutes < 60) {
            return $minutes . ' ' . translate('minutes');
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours < 24) {
            return $hours . 'h ' . $remainingMinutes . 'm';
        }
        
        $days = floor($hours / 24);
        return $days . ' ' . translate('days');
    }

    public function getSelectCustomerOptions(object $customers, int $selectedId = null): string
    {
        $output = '<option value="" disabled selected>' . translate('select_customer') . '</option>';
        
        foreach ($customers as $customer) {
            $selected = ($selectedId && $customer->id == $selectedId) ? 'selected' : '';
            $customerName = $customer->f_name . ' ' . $customer->l_name;
            $output .= '<option value="' . $customer->id . '" ' . $selected . '>' . 
                      $customerName . ' (' . $customer->phone . ')' . 
                      '</option>';
        }
        
        return $output;
    }
}