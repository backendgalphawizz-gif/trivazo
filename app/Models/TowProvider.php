<?php

namespace App\Models;

use App\Traits\CacheManagerTrait;
use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property string $company_name
 * @property string $business_license
 * @property string $insurance_info
 * @property string $service_area
 * @property int $max_simultaneous_trips
 * @property int $current_trips_count
 * @property float $rating
 * @property int $total_completed_trips
 * @property string $status
 * @property float $current_latitude
 * @property float $current_longitude
 * @property string $last_location_update
 * @property string $created_at
 * @property string $updated_at
 * 
 * // Accessors from User
 * @property string $owner_name
 * @property string $owner_phone
 * @property string $owner_email
 * @property string $owner_image
 */
class TowProvider extends Model
{
    use StorageTrait, CacheManagerTrait;

    protected $table = 'tow_providers';

    protected $fillable = [
        'user_id',
        'company_name',
        'business_license',
        'insurance_info',
        'service_area',
        'max_simultaneous_trips',
        'current_trips_count',
        'rating',
        'total_completed_trips',
        'status',
        'current_latitude',
        'current_longitude',
        'last_location_update',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'company_name' => 'string',
        'business_license' => 'string',
        'insurance_info' => 'string',
        'service_area' => 'string',
        'max_simultaneous_trips' => 'integer',
        'current_trips_count' => 'integer',
        'rating' => 'float',
        'total_completed_trips' => 'integer',
        'status' => 'string',
        'current_latitude' => 'float',
        'current_longitude' => 'float',
        'last_location_update' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'owner_name',
        'owner_phone',
        'owner_email',
        'owner_image_url',
        'license_document_url',
        'insurance_document_url',
        'availability_slots',
        'is_available',
        'status_badge'
    ];

    /**
     * Relation to your existing User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Active trips relationship
     */
    public function activeTrips(): HasMany
    {
        return $this->hasMany(ActiveTrip::class, 'provider_id', 'id')
                    ->whereNotIn('current_status', ['completed', 'cancelled']);
    }

    /**
     * Trip history
     */
    public function tripHistory(): HasMany
    {
        return $this->hasMany(ActiveTrip::class, 'provider_id', 'id')
                    ->where('current_status', 'completed');
    }

    /**
     * All tracking data through trips
     */
    public function trackingLocations(): HasMany
    {
        return $this->hasManyThrough(
            TripTracking::class,
            ActiveTrip::class,
            'provider_id', // Foreign key on active_trips
            'trip_id',     // Foreign key on trip_tracking
            'id',          // Local key on tow_providers
            'id'           // Local key on active_trips
        );
    }

    /**
     * Scope for available providers
     */
    public function scopeAvailable($query): Builder
    {
        return $query->where('status', 'available')
                     ->whereRaw('current_trips_count < max_simultaneous_trips');
    }

    /**
     * Scope for nearby providers using Haversine formula
     */
    public function scopeNearby($query, $lat, $lng, $radius = 10): Builder
    {
        $haversine = "(6371 * acos(cos(radians($lat)) 
                      * cos(radians(current_latitude)) 
                      * cos(radians(current_longitude) - radians($lng)) 
                      + sin(radians($lat)) 
                      * sin(radians(current_latitude))))";

        return $query->select('tow_providers.*')
                     ->selectRaw("{$haversine} AS distance")
                     ->whereRaw("{$haversine} <= ?", [$radius])
                     ->orderBy('distance');
    }

    /**
     * Scope for top rated providers
     */
    public function scopeTopRated($query): Builder
    {
        return $query->orderBy('rating', 'desc');
    }

    /**
     * Get owner's full name from User model
     */
    public function getOwnerNameAttribute(): string
{
    $user = $this->user;
    if (!$user) {
        return '';
    }
    return $user->f_name . ' ' . $user->l_name;
}

    /**
     * Get owner's phone from User model
     */
    public function getOwnerPhoneAttribute(): string
    {
        return $this->user->phone ?? '';
    }

    /**
     * Get owner's email from User model
     */
    public function getOwnerEmailAttribute(): string
    {
        return $this->user->email ?? '';
    }

    /**
     * Get owner's image from User model (using StorageTrait)
     */
    public function getOwnerImageUrlAttribute(): array
    {
        return $this->user->image_full_url ?? ['image_name' => '', 'storage' => 'public'];
    }

    /**
     * Get license document URL (using StorageTrait)
     */
    public function getLicenseDocumentUrlAttribute(): array
    {
        return $this->storageLink('provider/licenses', $this->business_license, 'public');
    }

    /**
     * Get insurance document URL (using StorageTrait)
     */
    public function getInsuranceDocumentUrlAttribute(): array
    {
        return $this->storageLink('provider/insurance', $this->insurance_info, 'public');
    }

    /**
     * Calculate available slots
     */
    public function getAvailabilitySlotsAttribute(): int
    {
        return $this->max_simultaneous_trips - $this->current_trips_count;
    }

    /**
     * Check if provider is available
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'available' && 
               $this->current_trips_count < $this->max_simultaneous_trips;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'available' => 'success',
            'busy' => 'warning',
            'offline' => 'secondary',
            'on_break' => 'info'
        ];

        return $badges[$this->status] ?? 'primary';
    }

    /**
     * Update provider's current location
     */
    public function updateLocation($latitude, $longitude): void
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_update' => now()
        ]);
    }

    /**
     * Boot method with storage handling (similar to your User model)
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            if ($model->isDirty('business_license')) {
                $storage = config('filesystems.disks.default') ?? 'public';
                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'business_license',
                ], [
                    'value' => $storage,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($model->isDirty('insurance_info')) {
                $storage = config('filesystems.disks.default') ?? 'public';
                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'insurance_info',
                ], [
                    'value' => $storage,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            CacheManagerTrait::cacheRemoveByType(type: 'tow_providers');
        });

        static::deleted(function ($model) {
            CacheManagerTrait::cacheRemoveByType(type: 'tow_providers');
        });
    }
}