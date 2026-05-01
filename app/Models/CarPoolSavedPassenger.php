<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPoolSavedPassenger extends Model
{
    protected $table = 'carpool_saved_passengers';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'gender',
        'age',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'age'        => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }
}
