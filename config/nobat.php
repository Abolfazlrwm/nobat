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
    | Online payments
    |--------------------------------------------------------------------------
    |
    | driver: resolves the gateway through App\Services\Payment\PaymentManager.
    |         "log" instantly succeeds without the network (local + tests);
    |         "zarinpal" talks to the ZarinPal v4 API (sandbox by default).
    | default_deadline_minutes: how long an unpaid booking holds its slot
    |         when the business does not override it.
    |
    */

    'payment' => [
        'driver' => env('NOBAT_PAYMENT_DRIVER', 'log'),
        'default_deadline_minutes' => (int) env('PAYMENT_HOLD_MINUTES', 15),
        'gateways' => [
            'zarinpal' => [
                'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
                'sandbox' => (bool) env('ZARINPAL_SANDBOX', true),
                'timeout' => 10,
                'retries' => 2,
            ],
        ],
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
