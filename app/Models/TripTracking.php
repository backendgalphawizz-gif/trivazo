<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $trip_id
 * @property float $latitude
 * @property float $longitude
 * @property float $speed
 * @property int $heading
 * @property string $recorded_at
 */
class TripTracking extends Model
{
    protected $table = 'trip_tracking';

    public $timestamps = false;

    protected $fillable = [
        'trip_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'trip_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
        'heading' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(ActiveTrip::class, 'trip_id');
    }

    public function getCoordinatesAttribute(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude
        ];
    }

    public function getSpeedFormattedAttribute(): string
    {
        return $this->speed ? number_format($this->speed, 1) . ' km/h' : '0 km/h';
    }
}