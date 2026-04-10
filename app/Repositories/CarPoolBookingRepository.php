<?php

namespace App\Repositories;

use App\Contracts\Repositories\CarPoolBookingRepositoryInterface;
use App\Models\CarPoolBooking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class CarPoolBookingRepository implements CarPoolBookingRepositoryInterface
{
    public function __construct(
        private readonly CarPoolBooking $booking
    ) {}

    public function add(array $data): string|object
    {
        return $this->booking->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->booking->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->booking->with($relations)
            ->when(!empty($orderBy), fn($q) => $q->orderBy(array_key_first($orderBy), array_values($orderBy)[0]));

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->booking->with($relations)
            ->when(isset($filters['passenger_id']), fn($q) => $q->where('passenger_id', $filters['passenger_id']))
            ->when(isset($filters['route_id']), fn($q) => $q->where('route_id', $filters['route_id']))
            ->when(isset($filters['driver_id']), fn($q) => $q->whereHas('route', fn($r) => $r->where('driver_id', $filters['driver_id'])))
            ->when(isset($filters['status']) && $filters['status'] !== 'all', fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['payment_status']) && $filters['payment_status'] !== 'all', fn($q) => $q->where('payment_status', $filters['payment_status']))
            ->when(isset($filters['date_from']), fn($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when($searchValue, fn($q) => $q->where('booking_code', 'like', "%$searchValue%"))
            ->when(!empty($orderBy), fn($q) => $q->orderBy(array_key_first($orderBy), array_values($orderBy)[0]));

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function update(string $id, array $data): bool
    {
        return $this->booking->where('id', $id)->update($data);
    }

    public function delete(array $params): bool
    {
        return $this->booking->where($params)->delete();
    }
}
