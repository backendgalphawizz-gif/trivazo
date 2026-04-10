<?php

namespace App\Enums;

enum CarPoolRouteStatus
{
    const OPEN      = 'open';
    const FULL      = 'full';
    const DEPARTED  = 'departed';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    const LIST = [
        self::OPEN,
        self::FULL,
        self::DEPARTED,
        self::COMPLETED,
        self::CANCELLED,
    ];

    const ACTIVE = [self::OPEN, self::FULL, self::DEPARTED];
}
