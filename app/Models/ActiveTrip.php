<?php

namespace App\Models;

use App\Traits\CacheManagerTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $request_id
 * @property int $provider_id
 * @property int $dispatcher_id
 * @property string $acceptance_time
 * @property string $en_route_time
 * @property string $arrival_time
 * @property string $start_time
 * @property string $completion_time
 * @property int $estimated_arrival_minutes
 * @property float $distance_estimate
 * @property string $current_status
 * @property string $cancellation_reason
 * @property string $created_at
 * @property string $updated_at
 */
class ActiveTrip extends Model
{
    use CacheManagerTrait;

    protected $table = 'active_trips';

    protected $fillable = [
        'request_id',
        'provider_id',
        'dispatcher_id',
        'acceptance_time',
        'en_route_time',
        'arrival_time',
        'start_time',
        'completion_time',
        'estimated_arrival_minutes',
        'distance_estimate',
        'current_status',
        'cancellation_reason',
    ];

    protected $casts = [
        'request_id' => 'integer',
        'provider_id' => 'integer',
        'dispatcher_id' => 'integer',
        'acceptance_time' => 'datetime',
        'en_route_time' => 'datetime',
        'arrival_time' => 'datetime',
        'start_time' => 'datetime',
        'completion_time' => 'datetime',
        'estimated_arrival_minutes' => 'integer',
        'distance_estimate' => 'float',
        'current_status' => 'string',
        'cancellation_reason' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(TowRequest::class, 'request_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TowProvider::class, 'provider_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function trackingLocations(): HasMany
    {
        return $this->hasMany(TripTracking::class, 'trip_id', 'id');
    }

    public function scopeOngoing($query): Builder
    {
        return $query->whereNotIn('current_status', ['completed', 'cancelled']);
    }

    public function scopeByStatus($query, $status): Builder
    {
        return $query->where('current_status', $status);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->acceptance_time) {
            return null;
        }

        $end = $this->completion_time ?? now();
        return $this->acceptance_time->diffForHumans($end, ['parts' => 2]);
    }

    public function getProgressPercentageAttribute(): int
    {
        $progressMap = [
            'assigned' => 10,
            'accepted' => 25,
            'en_route' => 50,
            'arrived' => 75,
            'in_progress' => 90,
            'completed' => 100
        ];

        return $progressMap[$this->current_status] ?? 0;
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'assigned' => 'secondary',
            'accepted' => 'info',
            'en_route' => 'primary',
            'arrived' => 'warning',
            'in_progress' => 'success',
            'completed' => 'dark'
        ];

        return $badges[$this->current_status] ?? 'light';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            CacheManagerTrait::cacheRemoveByType(type: 'active_trips');
            
            if ($model->isDirty('current_status')) {
                $model->request()->update(['status' => $model->current_status]);
            }
        });

        static::deleted(function ($model) {
            CacheManagerTrait::cacheRemoveByType(type: 'active_trips');
        });
    }
}