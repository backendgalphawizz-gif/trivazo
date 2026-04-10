<?php

namespace App\Models;

use App\Traits\CacheManagerTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $vehicle_info
 * @property string $pickup_location
 * @property float $pickup_latitude
 * @property float $pickup_longitude
 * @property string $destination
 * @property float $destination_latitude
 * @property float $destination_longitude
 * @property string $service_type
 * @property string $description
 * @property string $priority
 * @property string $status
 * @property float $estimated_price
 * @property float $final_price
 * @property string $created_at
 * @property string $updated_at
 */
class TowRequest extends Model
{
    use CacheManagerTrait;

    protected $table = 'tow_requests';

    protected $fillable = [
        'customer_id',
        'vehicle_info',
        'pickup_location',
        'pickup_latitude',
        'pickup_longitude',
        'destination',
        'destination_latitude',
        'destination_longitude',
        'service_type',
        'description',
        'priority',
        'status',
        'estimated_price',
        'final_price',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'vehicle_info' => 'string',
        'pickup_location' => 'string',
        'pickup_latitude' => 'float',
        'pickup_longitude' => 'float',
        'destination' => 'string',
        'destination_latitude' => 'float',
        'destination_longitude' => 'float',
        'service_type' => 'string',
        'description' => 'string',
        'priority' => 'string',
        'status' => 'string',
        'estimated_price' => 'float',
        'final_price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function activeTrip(): HasOne
    {
        return $this->hasOne(ActiveTrip::class, 'request_id', 'id');
    }

    public function scopePending($query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeByPriority($query): Builder
    {
        return $query->orderByRaw("FIELD(priority, 'emergency', 'high', 'normal', 'low')");
    }

    public function scopeWaitingLongerThan($query, $minutes): Builder
    {
        return $query->where('created_at', '<=', now()->subMinutes($minutes));
    }

    public function getWaitingTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getServiceTypeBadgeAttribute(): string
    {
        $badges = [
            'emergency' => 'danger',
            'scheduled' => 'info',
            'battery_jump' => 'warning',
            'flat_tire' => 'secondary',
            'fuel_delivery' => 'success'
        ];
        
        return $badges[$this->service_type] ?? 'primary';
    }

    public function getPriorityBadgeAttribute(): string
    {
        $badges = [
            'low' => 'success',
            'normal' => 'info',
            'high' => 'warning',
            'emergency' => 'danger'
        ];
        
        return $badges[$this->priority] ?? 'primary';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            CacheManagerTrait::cacheRemoveByType(type: 'tow_requests');
        });

        static::deleted(function ($model) {
            CacheManagerTrait::cacheRemoveByType(type: 'tow_requests');
        });
    }
}