<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelPayment extends Model
{
    protected $fillable = [
        'booking_id','transaction_id',
        'payment_method','payment_gateway',
        'amount','status','paid_at'
    ];

    protected $dates = ['paid_at'];

    public function booking()
    {
        return $this->belongsTo(HotelBooking::class,'booking_id');
    }
}