<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'hotel_bookings'; // ✅ ADD THIS

    protected $fillable = [
        'hotel_id',
        'room_id',
        'customer_name',
        'check_in',
        'check_out',
        'status'
    ];
}