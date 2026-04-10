<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInventory extends Model
{
    protected $table = 'room_inventory';
    
    protected $fillable = [
        'hotel_id', 'room_type_id', 'date', 'available_rooms',
        'booked_rooms', 'blocked_rooms', 'total_rooms', 'price',
        'discount_price', 'is_available'
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
    ];

    /**
     * Get the hotel that owns the inventory.
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    /**
     * Get the room type that owns the inventory.
     */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}