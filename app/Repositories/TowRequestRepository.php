<?php

namespace App\Repositories;

use App\Contracts\Repositories\TowRequestRepositoryInterface;
use App\Models\TowRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class TowRequestRepository implements TowRequestRepositoryInterface
{
    public function __construct(
        private readonly TowRequest $request
    )
    {
    }

    public function add(array $data): string|object
    {
        return $this->request->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->request->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->request->with($relations)
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                return $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->request->with($relations)
            ->when(isset($filters['status']) && $filters['status'] != 'all', function ($query) use ($filters) {
                return $query->where('status', $filters['status']);
            })
            ->when(isset($filters['priority']) && $filters['priority'] != 'all', function ($query) use ($filters) {
                return $query->where('priority', $filters['priority']);
            })
            ->when(isset($filters['service_type']) && $filters['service_type'] != 'all', function ($query) use ($filters) {
                return $query->where('service_type', $filters['service_type']);
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
                    $q->where('pickup_location', 'like', "%$searchValue%")
                      ->orWhere('destination', 'like', "%$searchValue%")
                      ->orWhere('description', 'like', "%$searchValue%")
                      ->orWhereHas('customer', function($customerQuery) use ($searchValue) {
                          $customerQuery->where('f_name', 'like', "%$searchValue%")
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
        return $this->request->find($id)->update($data);
    }

    public function delete(array $params): bool
    {
        $request = $this->request->where($params)->first();
        
        if ($request) {
            // Check if there's an active trip
            if ($request->activeTrip && !in_array($request->activeTrip->current_status, ['completed', 'cancelled'])) {
                return false;
            }
            
            return $request->delete();
        }
        
        return false;
    }

    public function getStatistics(array $filters = []): array
    {
        $query = $this->request->query();
        
        if (isset($filters['date_range'])) {
            if ($filters['date_range'] == 'today') {
                $query->whereDate('created_at', today());
            } elseif ($filters['date_range'] == 'this_week') {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($filters['date_range'] == 'this_month') {
                $query->whereMonth('created_at', now()->month);
            }
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'assigned' => (clone $query)->where('status', 'assigned')->count(),
            'in_progress' => (clone $query)->whereIn('status', ['accepted', 'en_route', 'arrived', 'in_progress'])->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'emergency' => (clone $query)->where('priority', 'emergency')->count(),
        ];
    }
}