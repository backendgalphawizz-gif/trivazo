<?php

namespace App\Enums;

enum CarPoolBookingStatus
{
    const PENDING_PAYMENT = 'pending_payment';
    const CONFIRMED       = 'confirmed';
    const DEPARTED        = 'departed';
    const COMPLETED       = 'completed';
    const CANCELLED       = 'cancelled';

    const LIST = [
        self::PENDING_PAYMENT,
        self::CONFIRMED,
        self::DEPARTED,
        self::COMPLETED,
        self::CANCELLED,
    ];

    const CANCELLABLE = [self::PENDING_PAYMENT, self::CONFIRMED];
}
