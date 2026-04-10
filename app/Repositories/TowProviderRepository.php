<?php

namespace App\Repositories;

use App\Contracts\Repositories\TowProviderRepositoryInterface;
use App\Models\TowProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TowProviderRepository implements TowProviderRepositoryInterface
{
    public function __construct(
        private readonly TowProvider $provider,
        private readonly User $user
    )
    {
    }

    public function add(array $data): string|object
    {
        return $this->provider->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->provider->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->provider->with($relations)
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                return $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->provider->with($relations)
            ->when(isset($filters['status']) && $filters['status'] != 'all', function ($query) use ($filters) {
                return $query->where('status', $filters['status']);
            })
            ->when(isset($filters['rating']) && $filters['rating'] != 'all', function ($query) use ($filters) {
                return $query->where('rating', '>=', $filters['rating']);
            })
            ->when(isset($filters['service_area']), function ($query) use ($filters) {
                return $query->where('service_area', 'LIKE', '%' . $filters['service_area'] . '%');
            })
            ->when(isset($searchValue), function ($query) use ($searchValue) {
                return $query->where(function($q) use ($searchValue) {
                    $q->where('company_name', 'like', "%$searchValue%")
                      ->orWhere('business_license', 'like', "%$searchValue%")
                      ->orWhereHas('user', function($userQuery) use ($searchValue) {
                          $userQuery->where('f_name', 'like', "%$searchValue%")
                                    ->orWhere('l_name', 'like', "%$searchValue%")
                                    ->orWhere('phone', 'like', "%$searchValue%")
                                    ->orWhere('email', 'like', "%$searchValue%");
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
        return $this->provider->find($id)->update($data);
    }

    public function delete(array $params): bool
    {
        $provider = $this->provider->where($params)->first();
        
        if ($provider) {
            // Delete related files
            if ($provider->business_license) {
                // Delete license file
            }
            if ($provider->insurance_info) {
                // Delete insurance file
            }
            
            // Optionally revert user role
            if ($provider->user_id) {
                $this->user->where('id', $provider->user_id)->update(['role' => 'customer']);
            }
            
            return $provider->delete();
        }
        
        return false;
    }



    public function getNearbyAvailableProviders(float $latitude, float $longitude, float $radius = 10): Collection
    {
        $haversine = "(6371 * acos(cos(radians($latitude)) 
                      * cos(radians(current_latitude)) 
                      * cos(radians(current_longitude) - radians($longitude)) 
                      + sin(radians($latitude)) 
                      * sin(radians(current_latitude))))";

        return $this->provider->select('tow_providers.*')
            ->selectRaw("{$haversine} AS distance")
            ->where('status', 'available')
            ->whereRaw('current_trips_count < max_simultaneous_trips')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get();
    }

    public function updateLocation(int $providerId, float $latitude, float $longitude): bool
    {
        return $this->provider->where('id', $providerId)->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_update' => now()
        ]);
    }

    public function incrementTripCount(int $providerId): bool
    {
        $provider = $this->provider->find($providerId);
        if ($provider) {
            $newCount = $provider->current_trips_count + 1;
            $updateData = ['current_trips_count' => $newCount];
            
            if ($newCount >= $provider->max_simultaneous_trips) {
                $updateData['status'] = 'busy';
            }
            
            return $provider->update($updateData);
        }
        return false;
    }

    public function decrementTripCount(int $providerId): bool
    {
        $provider = $this->provider->find($providerId);
        if ($provider && $provider->current_trips_count > 0) {
            $newCount = $provider->current_trips_count - 1;
            $updateData = ['current_trips_count' => $newCount];
            
            if ($newCount < $provider->max_simultaneous_trips && $provider->status == 'busy') {
                $updateData['status'] = 'available';
            }
            
            return $provider->update($updateData);
        }
        return false;
    }

    public function getStatistics(): array
    {
        return [
            'total' => $this->provider->count(),
            'available' => $this->provider->where('status', 'available')->count(),
            'busy' => $this->provider->where('status', 'busy')->count(),
            'offline' => $this->provider->where('status', 'offline')->count(),
            'on_break' => $this->provider->where('status', 'on_break')->count(),
            'total_trips' => $this->provider->sum('total_completed_trips'),
            'newCounter' => 0,
        ];
    }
}