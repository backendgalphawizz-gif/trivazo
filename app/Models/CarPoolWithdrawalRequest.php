<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPoolWithdrawalRequest extends Model
{
    protected $table = 'carpool_driver_withdrawal_requests';

    protected $fillable = [
        'driver_id',
        'amount',
        'status',
        'account_details',
        'admin_note',
        'processed_at',
    ];

    protected $casts = [
        'driver_id'       => 'integer',
        'amount'          => 'float',
        'account_details' => 'array',
        'processed_at'    => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(CarPoolDriver::class, 'driver_id');
    }

    public function scopePending($query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query): Builder
    {
        return $query->where('status', 'rejected');
    }
}
