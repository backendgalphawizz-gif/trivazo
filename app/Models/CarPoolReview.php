<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPoolReview extends Model
{
    protected $table = 'carpool_reviews';

    protected $fillable = [
        'booking_id',
        'route_id',
        'driver_id',
        'passenger_id',
        'rating',
        'comment',
        'status',
    ];

    protected $casts = [
        'booking_id'   => 'integer',
        'route_id'     => 'integer',
        'driver_id'    => 'integer',
        'passenger_id' => 'integer',
        'rating'       => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CarPoolBooking::class, 'booking_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(CarPoolDriver::class, 'driver_id');
    }
}
