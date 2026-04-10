<?php

namespace App\Models;

use App\Enums\CarPoolRouteStatus;
use App\Traits\CacheManagerTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class CarPoolDriver extends Authenticatable
{
    use HasApiTokens, Notifiable, CacheManagerTrait;

    protected $table = 'carpool_drivers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'device_token',
        'fcm_token',
        'vehicle_type',
        'vehicle_number',
        'vehicle_model',
        'vehicle_color',
        'vehicle_capacity',
        'license_number',
        'license_doc',
        'profile_image',
        'status',
        'is_verified',
        'is_online',
        'rating',
        'total_completed_rides',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'id'                     => 'integer',
        'vehicle_capacity'       => 'integer',
        'is_verified'            => 'boolean',
        'is_online'              => 'integer',
        'rating'                 => 'float',
        'total_completed_rides'  => 'integer',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function wallet(): HasOne
    {
        return $this->hasOne(CarPoolDriverWallet::class, 'driver_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(CarPoolRoute::class, 'driver_id');
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(CarPoolWithdrawalRequest::class, 'driver_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CarPoolReview::class, 'driver_id');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true)->where('status', 'active');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (CarPoolDriver $driver) {
            CarPoolDriverWallet::create(['driver_id' => $driver->id]);
        });

        static::saved(function () {
            CacheManagerTrait::cacheRemoveByType(type: 'carpool_drivers');
        });

        static::deleted(function () {
            CacheManagerTrait::cacheRemoveByType(type: 'carpool_drivers');
        });
    }
}
