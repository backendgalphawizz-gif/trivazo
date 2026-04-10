<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $table = 'hotels';

    protected $fillable = [
        'seller_id',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'check_in_time',
        'check_out_time',
        'star_rating',
        'total_rooms',
        'featured_image',
        'image_alt_text',
        'gallery_images',
        'amenities',
        'policies',
        'nearby_places',
        'status',
        'is_featured',
        'views_count',
        'commission_rate',
        'cancellation_policy',
        'rejection_reason',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'amenities' => 'array',
        'policies' => 'array',
        'nearby_places' => 'array',
        'is_featured' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the seller that owns the hotel.
     */
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Get the room types for the hotel.
     */
    public function roomTypes()
    {
        return $this->hasMany(RoomType::class, 'hotel_id');
    }

    /**
     * Get the bookings for the hotel.
     */
    public function bookings()
    {
        return $this->hasMany(HotelBooking::class, 'hotel_id');
    }

    /**
     * Get the admin who approved the hotel.
     */
    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function amenityDetails()
    {
        return Amenities::whereIn('id', $this->amenities ?? [])->get();
    }
}
