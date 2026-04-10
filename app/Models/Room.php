<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

   
    protected $fillable = [
        'hotel_id',
        'room_type',
        'featured_image',
        'gallery',
        'single_price',
        'single_sale_price',
        'double_price',
        'double_sale_price',
        'extra_adult_price',
        'extra_child_price',
        'gst',
        'rooms_available',
        'room_size',
        'max_adults',
        'max_children',
        'attributes',
        'status',
    ];

  
    protected $casts = [
        'gallery'    => 'array',
        'attributes' => 'array',
        'status'     => 'boolean',
        'single_price' => 'float',
        'single_sale_price' => 'float',
        'double_price' => 'float',
        'double_sale_price' => 'float',
        'extra_adult_price' => 'float',
        'extra_child_price' => 'float',
        'gst' => 'float',
    ];

  
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }



   
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    
    public function scopeAvailable($query)
    {
        return $query->where('rooms_available', '>', 0);
    }

   

    
    public function getSingleFinalPriceAttribute()
    {
        $price = $this->single_sale_price ?? $this->single_price;
        return $price + ($price * $this->gst / 100);
    }

    
    public function getDoubleFinalPriceAttribute()
    {
        $price = $this->double_sale_price ?? $this->double_price;
        return $price + ($price * $this->gst / 100);
    }
}