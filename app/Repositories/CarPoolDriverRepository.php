<?php

namespace App\Repositories;

use App\Contracts\Repositories\CarPoolDriverRepositoryInterface;
use App\Models\CarPoolDriver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class CarPoolDriverRepository implements CarPoolDriverRepositoryInterface
{
    public function __construct(
        private readonly CarPoolDriver $driver
    ) {}

    public function add(array $data): string|object
    {
        return $this->driver->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->driver->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->driver->with($relations)
            ->when(!empty($orderBy), fn($q) => $q->orderBy(array_key_first($orderBy), array_values($orderBy)[0]));

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->driver->with($relations)
            ->when(isset($filters['status']) && $filters['status'] !== 'all', fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['is_verified']), fn($q) => $q->where('is_verified', $filters['is_verified']))
            ->when($searchValue, fn($q) => $q->where(function ($inner) use ($searchValue) {
                $inner->where('name', 'like', "%$searchValue%")
                    ->orWhere('phone', 'like', "%$searchValue%")
                    ->orWhere('email', 'like', "%$searchValue%")
                    ->orWhere('vehicle_number', 'like', "%$searchValue%");
            }))
            ->when(!empty($orderBy), fn($q) => $q->orderBy(array_key_first($orderBy), array_values($orderBy)[0]));

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function update(string $id, array $data): bool
    {
        return $this->driver->where('id', $id)->update($data);
    }

    public function delete(array $params): bool
    {
        return $this->driver->where($params)->delete();
    }

    public function findByPhone(string $phone): ?CarPoolDriver
    {
        return $this->driver->where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?CarPoolDriver
    {
        return $this->driver->where('email', $email)->first();
    }
}
