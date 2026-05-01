<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarPoolVehicleCategory extends Model
{
    protected $table = 'carpool_vehicle_categories';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'id'          => 'integer',
        'is_active'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function drivers(): HasMany
    {
        return $this->hasMany(CarPoolDriver::class, 'vehicle_category_id');
    }

    public static function activeOrdered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
