<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPoolDriverWallet extends Model
{
    protected $table = 'carpool_driver_wallets';

    protected $fillable = [
        'driver_id',
        'pending_balance',
        'available_balance',
        'total_earned',
        'total_withdrawn',
    ];

    protected $casts = [
        'driver_id'         => 'integer',
        'pending_balance'   => 'float',
        'available_balance' => 'float',
        'total_earned'      => 'float',
        'total_withdrawn'   => 'float',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(CarPoolDriver::class, 'driver_id');
    }
}
