<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPoolBookingPassenger extends Model
{
    protected $table = 'carpool_booking_passengers';

    protected $fillable = [
        'booking_id',
        'name',
        'phone',
        'gender',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CarPoolBooking::class, 'booking_id');
    }
}
