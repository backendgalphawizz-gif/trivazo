<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPoolTransaction extends Model
{
    protected $table = 'carpool_transactions';

    protected $fillable = [
        'booking_id',
        'route_id',
        'payer_id',
        'driver_id',
        'transaction_type',
        'amount',
        'admin_commission',
        'driver_amount',
        'payment_method',
        'payment_status',
        'gateway_reference',
    ];

    protected $casts = [
        'booking_id'       => 'integer',
        'route_id'         => 'integer',
        'payer_id'         => 'integer',
        'driver_id'        => 'integer',
        'amount'           => 'float',
        'admin_commission' => 'float',
        'driver_amount'    => 'float',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
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
