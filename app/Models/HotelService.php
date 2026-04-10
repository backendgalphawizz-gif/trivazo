<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelService extends Model
{
	use HasFactory;

	protected $fillable = [
		'hotel_id',
		'title',
		'short_description',
		'image',
		'service_type',
		'sort_order',
		'status',
	];

	protected $casts = [
		'hotel_id' => 'integer',
		'sort_order' => 'integer',
		'status' => 'integer',
	];

	public function hotel(): BelongsTo
	{
		return $this->belongsTo(Hotel::class);
	}
}
