<?php

namespace App\Repositories;

use App\Contracts\Repositories\CarPoolRouteRepositoryInterface;
use App\Enums\CarPoolRouteStatus;
use App\Models\CarPoolRoute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class CarPoolRouteRepository implements CarPoolRouteRepositoryInterface
{
    public function __construct(
        private readonly CarPoolRoute $route
    ) {}

    public function add(array $data): string|object
    {
        return $this->route->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->route->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->route->with($relations)
            ->when(!empty($orderBy), fn($q) => $q->orderBy(array_key_first($orderBy), array_values($orderBy)[0]));

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->route->with($relations)
            ->when(isset($filters['driver_id']), fn($q) => $q->where('driver_id', $filters['driver_id']))
            ->when(isset($filters['route_status']) && $filters['route_status'] !== 'all', fn($q) => $q->where('route_status', $filters['route_status']))
            ->when(isset($filters['ride_type']), fn($q) => $q->where('ride_type', $filters['ride_type']))
            ->when(isset($filters['date_from']), fn($q) => $q->whereDate('departure_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->whereDate('departure_at', '<=', $filters['date_to']))
            ->when($searchValue, fn($q) => $q->where(function ($inner) use ($searchValue) {
                $inner->where('origin_name', 'like', "%$searchValue%")
                    ->orWhere('destination_name', 'like', "%$searchValue%");
            }))
            ->when(!empty($orderBy), fn($q) => $q->orderBy(array_key_first($orderBy), array_values($orderBy)[0]));

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function update(string $id, array $data): bool
    {
        return $this->route->where('id', $id)->update($data);
    }

    public function delete(array $params): bool
    {
        return $this->route->where($params)->delete();
    }

    public function searchRoutes(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        string $date,
        int $seats,
        float $radiusKm = 5.0,
        int|string $dataLimit = DEFAULT_DATA_LIMIT
    ): Collection|LengthAwarePaginator {
        $query = $this->route->with(['driver'])
            ->where('route_status', CarPoolRouteStatus::OPEN)
            ->where('available_seats', '>=', $seats)
            ->whereDate('departure_at', $date)
            ->where('departure_at', '>', now())
            ->nearOrigin($originLat, $originLng, $radiusKm)
            ->nearDestination($destLat, $destLng, $radiusKm)
            ->orderBy('departure_at');

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }
}
