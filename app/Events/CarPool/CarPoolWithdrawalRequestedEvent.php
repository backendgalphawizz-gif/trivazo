<?php

namespace App\Events\CarPool;

use App\Models\CarPoolWithdrawalRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarPoolWithdrawalRequestedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CarPoolWithdrawalRequest $withdrawalRequest) {}
}
