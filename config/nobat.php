<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking settings
    |--------------------------------------------------------------------------
    |
    | lead_time_minutes: minimum notice required before a slot can be booked.
    | max_advance_days:  how far into the future customers may book.
    |
    */

    'booking' => [
        'lead_time_minutes' => 30,
        'max_advance_days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS notifications
    |--------------------------------------------------------------------------
    |
    | Driver used to deliver SMS messages. The "log" driver writes messages
    | to the Laravel log which is handy for local development; replace it
    | with a real gateway (Kavenegar, FarazSMS, ...) by implementing
    | App\Services\Sms\Contracts\SmsDriver.
    |
    */

    'sms' => [
        'driver' => env('NOBAT_SMS_DRIVER', 'log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    |
    | Throwaway demo deployments (the Render demo image sets NOBAT_DEMO)
    | show the ready-made demo accounts on the login page with one-click
    | sign-in. Keep this disabled for real installations.
    |
    */

    'demo' => env('NOBAT_DEMO', false),

];
