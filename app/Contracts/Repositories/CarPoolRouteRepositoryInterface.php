<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CarPoolRouteRepositoryInterface extends RepositoryInterface
{
    public function searchRoutes(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        string $date,
        int $seats,
        float $radiusKm = 5.0,
        int|string $dataLimit = DEFAULT_DATA_LIMIT
    ): Collection|LengthAwarePaginator;
}
