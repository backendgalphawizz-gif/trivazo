<?php

namespace App\Repositories;

use App\Contracts\Repositories\ActiveTripRepositoryInterface;
use App\Models\ActiveTrip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ActiveTripRepository implements ActiveTripRepositoryInterface
{
    public function __construct(
        private readonly ActiveTrip $trip
    )
    {
    }

    public function add(array $data): string|object
    {
        return $this->trip->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->trip->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->trip->with($relations)
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                return $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->trip->with($relations)
            ->when(isset($filters['current_status']) && $filters['current_status'] != 'all', function ($query) use ($filters) {
                return $query->where('current_status', $filters['current_status']);
            })
            ->when(isset($filters['provider_id']), function ($query) use ($filters) {
                return $query->where('provider_id', $filters['provider_id']);
            })
            ->when(isset($filters['date_range']), function ($query) use ($filters) {
                if ($filters['date_range'] == 'today') {
                    return $query->whereDate('created_at', today());
                } elseif ($filters['date_range'] == 'this_week') {
                    return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($filters['date_range'] == 'this_month') {
                    return $query->whereMonth('created_at', now()->month);
                }
            })
            ->when(isset($searchValue), function ($query) use ($searchValue) {
                return $query->where(function($q) use ($searchValue) {
                    $q->whereHas('request.customer', function($customerQuery) use ($searchValue) {
                        $customerQuery->where('f_name', 'like', "%$searchValue%")
                                      ->orWhere('l_name', 'like', "%$searchValue%")
                                      ->orWhere('phone', 'like', "%$searchValue%");
                    })->orWhereHas('provider', function($providerQuery) use ($searchValue) {
                        $providerQuery->where('company_name', 'like', "%$searchValue%")
                                      ->orWhereHas('user', function($userQuery) use ($searchValue) {
                                          $userQuery->where('f_name', 'like', "%$searchValue%")
                                                    ->orWhere('l_name', 'like', "%$searchValue%");
                                      });
                    });
                });
            })
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                return $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        $filters += ['searchValue' => $searchValue];
        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    public function update(string|int $id, array $data): bool
    {
        return $this->trip->find($id)->update($data);
    }

    public function delete(array $params): bool
    {
        $trip = $this->trip->where($params)->first();
        
        if ($trip) {
            // Delete related tracking data
            $trip->trackingLocations()->delete();
            
            return $trip->delete();
        }
        
        return false;
    }

    public function getActiveTripsWithLocation(): Collection
    {
        return $this->trip->with(['request.customer', 'provider.user'])
            ->whereNotIn('current_status', ['completed', 'cancelled'])
            ->whereHas('provider', function($query) {
                $query->whereNotNull('current_latitude')
                      ->whereNotNull('current_longitude');
            })
            ->get();
    }

    public function getStatistics(): array
    {
        return [
            'total' => $this->trip->whereNotIn('current_status', ['completed', 'cancelled'])->count(),
            'assigned' => $this->trip->where('current_status', 'assigned')->count(),
            'accepted' => $this->trip->where('current_status', 'accepted')->count(),
            'en_route' => $this->trip->where('current_status', 'en_route')->count(),
            'arrived' => $this->trip->where('current_status', 'arrived')->count(),
            'in_progress' => $this->trip->where('current_status', 'in_progress')->count(),
        ];
    }

    public function getProviderActiveTrip(int $providerId): ?Model
    {
        return $this->trip->where('provider_id', $providerId)
            ->whereNotIn('current_status', ['completed', 'cancelled'])
            ->with(['request', 'trackingLocations' => function($query) {
                $query->latest()->limit(1);
            }])
            ->first();
    }

    public function updateTripStatus(int $tripId, string $status, array $additionalData = []): bool
    {
        $trip = $this->trip->find($tripId);
        
        if ($trip) {
            $updateData = array_merge(['current_status' => $status], $additionalData);
            return $trip->update($updateData);
        }
        
        return false;
    }
}