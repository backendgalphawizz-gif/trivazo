<?php

namespace App\Models;

use App\Enums\CarPoolRouteStatus;
use App\Traits\CacheManagerTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarPoolRoute extends Model
{
    use CacheManagerTrait;

    protected $table = 'carpool_routes';

    protected $fillable = [
        'driver_id',
        'origin_name',
        'origin_lat',
        'origin_lng',
        'destination_name',
        'destination_lat',
        'destination_lng',
        'waypoints',
        'ride_type',
        'departure_at',
        'estimated_duration_min',
        'estimated_distance_km',
        'total_seats',
        'available_seats',
        'price_per_seat',
        'currency',
        'route_status',
        'note',
    ];

    protected $casts = [
        'driver_id'              => 'integer',
        'origin_lat'             => 'float',
        'origin_lng'             => 'float',
        'destination_lat'        => 'float',
        'destination_lng'        => 'float',
        'waypoints'              => 'array',
        'departure_at'           => 'datetime',
        'estimated_duration_min' => 'integer',
        'estimated_distance_km'  => 'float',
        'total_seats'            => 'integer',
        'available_seats'        => 'integer',
        'price_per_seat'         => 'float',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(CarPoolDriver::class, 'driver_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(CarPoolBooking::class, 'route_id');
    }

    public function confirmedBookings(): HasMany
    {
        return $this->hasMany(CarPoolBooking::class, 'route_id')
            ->whereIn('status', ['confirmed', 'departed', 'completed']);
    }

    public function scopeOpen($query): Builder
    {
        return $query->where('route_status', CarPoolRouteStatus::OPEN);
    }

    public function scopeAvailable($query): Builder
    {
        return $query->whereIn('route_status', [CarPoolRouteStatus::OPEN, CarPoolRouteStatus::FULL])
            ->where('available_seats', '>', 0);
    }

    public function scopeUpcoming($query): Builder
    {
        return $query->where('departure_at', '>', now());
    }

    public function scopeByDriver($query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Haversine-based proximity scope (radius in km).
     */
    public function scopeNearOrigin($query, float $lat, float $lng, float $radiusKm = 5): Builder
    {
        return $query->whereRaw(
            '(6371 * acos(cos(radians(?)) * cos(radians(origin_lat)) * cos(radians(origin_lng) - radians(?)) + sin(radians(?)) * sin(radians(origin_lat)))) < ?',
            [$lat, $lng, $lat, $radiusKm]
        );
    }

    public function scopeNearDestination($query, float $lat, float $lng, float $radiusKm = 5): Builder
    {
        return $query->whereRaw(
            '(6371 * acos(cos(radians(?)) * cos(radians(destination_lat)) * cos(radians(destination_lng) - radians(?)) + sin(radians(?)) * sin(radians(destination_lat)))) < ?',
            [$lat, $lng, $lat, $radiusKm]
        );
    }

    public function getIsFullAttribute(): bool
    {
        return $this->available_seats <= 0;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            cacheRemoveByType(type: 'carpool_routes');
        });

        static::deleted(function () {
            cacheRemoveByType(type: 'carpool_routes');
        });
    }
}
