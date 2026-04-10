<?php

namespace App\Listeners\CarPool;

use App\Events\CarPool\CarPoolRideCompletedEvent;
use App\Services\CarPoolDriverWalletService;

class SettleDriverWalletOnCompletionListener
{
    public function __construct(private readonly CarPoolDriverWalletService $walletService) {}

    public function handle(CarPoolRideCompletedEvent $event): void
    {
        $this->walletService->settleOnCompletion($event->booking);
    }
}
