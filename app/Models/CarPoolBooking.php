<?php

namespace App\Models;

use App\Enums\CarPoolBookingStatus;
use App\Traits\CacheManagerTrait;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CarPoolBooking extends Model
{
    use CacheManagerTrait;

    protected $table = 'carpool_bookings';

    protected $fillable = [
        'route_id',
        'passenger_id',
        'user_id',
        'pickup_name',
        'pickup_lat',
        'pickup_lng',
        'drop_name',
        'drop_lat',
        'drop_lng',
        'seat_count',
        'booking_code',
        'status',
        'fare_total',
        'tax_amount',
        'final_amount',
        'admin_commission_amount',
        'driver_amount',
        'payment_method',
        'payment_status',
        'gateway_reference',
        'cancelled_by',
        'cancellation_reason',
        'confirmed_at',
        'departed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'route_id'                => 'integer',
        'passenger_id'            => 'integer',
        'pickup_lat'              => 'float',
        'pickup_lng'              => 'float',
        'drop_lat'                => 'float',
        'drop_lng'                => 'float',
        'seat_count'              => 'integer',
        'fare_total'              => 'float',
        'tax_amount'              => 'float',
        'final_amount'            => 'float',
        'admin_commission_amount' => 'float',
        'driver_amount'           => 'float',
        'confirmed_at'            => 'datetime',
        'departed_at'             => 'datetime',
        'completed_at'            => 'datetime',
        'cancelled_at'            => 'datetime',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(CarPoolRoute::class, 'route_id');
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(CarPoolBookingPassenger::class, 'booking_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(CarPoolTransaction::class, 'booking_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(CarPoolReview::class, 'booking_id');
    }

    public function scopeByPassenger($query, int $passengerId): Builder
    {
        return $query->where('passenger_id', $passengerId);
    }

    public function scopeByRoute($query, int $routeId): Builder
    {
        return $query->where('route_id', $routeId);
    }

    public function scopeConfirmedOrActive($query): Builder
    {
        return $query->whereIn('status', [
            CarPoolBookingStatus::CONFIRMED,
            CarPoolBookingStatus::DEPARTED,
        ]);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, CarPoolBookingStatus::CANCELLABLE);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            cacheRemoveByType(type: 'carpool_bookings');
        });

        static::deleted(function () {
            cacheRemoveByType(type: 'carpool_bookings');
        });
    }
}
