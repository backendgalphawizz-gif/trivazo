<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commission
    |--------------------------------------------------------------------------
    | Percentage of fare_total taken by admin per completed booking (0–100).
    | Override at runtime via business_settings key: carpool_commission_percentage
    */
    'commission_percentage' => env('CARPOOL_COMMISSION_PERCENT', 10),

    /*
    |--------------------------------------------------------------------------
    | Wallet
    |--------------------------------------------------------------------------
    */
    'min_withdrawal_amount' => env('CARPOOL_MIN_WITHDRAWAL', 10),

    /*
    |--------------------------------------------------------------------------
    | Payment methods accepted for carpool bookings
    |--------------------------------------------------------------------------
    */
    'allowed_payment_methods' => ['wallet', 'online'],

    /*
    |--------------------------------------------------------------------------
    | Booking behaviour
    |--------------------------------------------------------------------------
    | booking_cancellation_window_minutes: passenger can cancel free within N
    |   minutes of confirming, before departure.
    | instant_ride_max_window_minutes: an "instant" ride departure window.
    */
    'booking_cancellation_window_minutes' => env('CARPOOL_CANCEL_WINDOW', 15),
    'instant_ride_max_window_minutes'     => env('CARPOOL_INSTANT_WINDOW', 30),

];
