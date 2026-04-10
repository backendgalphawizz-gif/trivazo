<?php

namespace App\Listeners\CarPool;

use App\Events\CarPool\CarPoolBookingConfirmedEvent;
use App\Services\CarPoolNotificationService;

class NotifyPassengerOnBookingConfirmedListener
{
    public function __construct(private readonly CarPoolNotificationService $notificationService) {}

    public function handle(CarPoolBookingConfirmedEvent $event): void
    {
        $this->notificationService->notifyPassengerBookingConfirmed($event->booking);
    }
}
