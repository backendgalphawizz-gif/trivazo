<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoom extends Model
{
    protected $table = 'booking_rooms';
    
    protected $fillable = [
        'booking_id', 'room_type_id', 'room_number', 'price_per_night', 'total_price'
    ];

    /**
     * Get the booking that owns the booking room.
     */
    public function booking()
    {
        return $this->belongsTo(HotelBooking::class, 'booking_id');
    }

    /**
     * Get the room type that owns the booking room.
     */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}