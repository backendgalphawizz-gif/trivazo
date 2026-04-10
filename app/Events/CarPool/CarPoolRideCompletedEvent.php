<?php

namespace App\Events\CarPool;

use App\Models\CarPoolBooking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarPoolRideCompletedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CarPoolBooking $booking) {}
}
