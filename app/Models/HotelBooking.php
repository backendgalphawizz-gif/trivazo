<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelBooking extends Model
{
    protected $table = 'hotel_bookings';
    
    protected $fillable = [
        'booking_number', 'customer_id', 'hotel_id', 'room_type_id',
        'check_in_date', 'check_out_date', 'nights', 'rooms_count',
        'adults', 'children', 'total_price', 'subtotal', 'tax_amount',
        'discount_amount', 'coupon_code', 'coupon_discount', 'special_requests',
        'guest_details', 'payment_method', 'payment_status', 'transaction_id',
        'booking_status', 'cancellation_reason', 'cancelled_by', 'cancelled_at',
        'refund_amount', 'refund_status', 'checked_in_at', 'checked_out_at',
        'booking_source'
    ];

    protected $casts = [
        'guest_details' => 'array',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the booking.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the hotel that owns the booking.
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    /**
     * Get the room type that owns the booking.
     */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    /**
     * Get the booking rooms.
     */
    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class, 'booking_id');
    }

    /**
     * Get the user who cancelled the booking.
     */
    public function cancelledBy()
    {
        return $this->belongsTo(Admin::class, 'cancelled_by');
    }
}