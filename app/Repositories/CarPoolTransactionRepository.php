<?php

namespace App\Repositories;

use App\Contracts\Repositories\CarPoolTransactionRepositoryInterface;
use App\Models\CarPoolTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class CarPoolTransactionRepository implements CarPoolTransactionRepositoryInterface
{
    public function __construct(
        private readonly CarPoolTransaction $transaction
    ) {}

    public function add(array $data): string|object
    {
        return $this->transaction->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->transaction->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->transaction->with($relations)
            ->when(!empty($orderBy), fn($q) => $q->orderBy(array_key_first($orderBy), array_values($orderBy)[0]));

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->transaction->with($relations)
            ->when(isset($filters['driver_id']), fn($q) => $q->where('driver_id', $filters['driver_id']))
            ->when(isset($filters['booking_id']), fn($q) => $q->where('booking_id', $filters['booking_id']))
            ->when(isset($filters['transaction_type']), fn($q) => $q->where('transaction_type', $filters['transaction_type']))
            ->when(isset($filters['payment_status']) && $filters['payment_status'] !== 'all', fn($q) => $q->where('payment_status', $filters['payment_status']))
            ->when(isset($filters['date_from']), fn($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when(!empty($orderBy), fn($q) => $q->orderBy(array_key_first($orderBy), array_values($orderBy)[0]));

        return $dataLimit === 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function update(string $id, array $data): bool
    {
        return $this->transaction->where('id', $id)->update($data);
    }

    public function delete(array $params): bool
    {
        return $this->transaction->where($params)->delete();
    }
}
