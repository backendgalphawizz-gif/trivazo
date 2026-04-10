<?php

namespace App\Contracts\Repositories;

use App\Models\CarPoolDriver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface CarPoolDriverRepositoryInterface extends RepositoryInterface
{
    public function findByPhone(string $phone): ?CarPoolDriver;
    public function findByEmail(string $email): ?CarPoolDriver;
}
