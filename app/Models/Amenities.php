<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenities extends Model
{
    protected $table = 'hotel_amenities';

    protected $fillable = [
        'name', 'icon', 'category', 'status'
    ];

    public function scopeStatus($query): Builder
    {
        return $query->where('status', 1);
    }

}
