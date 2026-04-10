<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $table = 'room_types';
    
    protected $fillable = [
        'hotel_id', 'name', 'slug', 'description', 'size_sqft', 'max_occupancy',
        'bed_type', 'bed_count', 'total_rooms', 'base_price', 'discount_price',
        'discount_type', 'discount_start_date', 'discount_end_date', 'featured_image',
        'image_alt_text', 'gallery_images', 'amenities', 'is_breakfast_included',
        'breakfast_price', 'is_refundable', 'cancellation_days', 'cancellation_charge',
        'status'
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'amenities' => 'array',
        'is_breakfast_included' => 'boolean',
        'is_refundable' => 'boolean',
    ];

    /**
     * Get the hotel that owns the room type.
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    /**
     * Get the bookings for the room type.
     */
    public function bookings()
    {
        return $this->hasMany(HotelBooking::class, 'room_type_id');
    }

    /**
     * Get the inventory for the room type.
     */
    public function inventory()
    {
        return $this->hasMany(RoomInventory::class, 'room_type_id');
    }
}