<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarPoolStatusHistory extends Model
{
    protected $table = 'carpool_status_histories';

    protected $fillable = [
        'target_type',
        'target_id',
        'old_status',
        'new_status',
        'actor_type',
        'actor_id',
        'note',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'actor_id'  => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
