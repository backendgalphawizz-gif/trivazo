<?php

namespace App\Listeners\CarPool;

use App\Events\CarPool\CarPoolRideCompletedEvent;
use App\Services\CarPoolNotificationService;

class NotifyPassengerOnRideCompletedListener
{
    public function __construct(private readonly CarPoolNotificationService $notificationService) {}

    public function handle(CarPoolRideCompletedEvent $event): void
    {
        $this->notificationService->notifyPassengerRideCompleted($event->booking);
    }
}
