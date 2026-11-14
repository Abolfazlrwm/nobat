<?php

/*
|--------------------------------------------------------------------------
| Legal documents — privacy policy & terms of service (en)
|--------------------------------------------------------------------------
| Rendered server-side on /privacy and /terms. The copy describes what
| Nobat actually does with data — no boilerplate promises the code does
| not keep.
*/

return [

    'privacy' => [
        'title' => 'Privacy Policy',
        'updated' => 'Last updated: November 2026',

        'sections' => [
            [
                'heading' => 'What we collect',
                'body' => [
                    'A booking needs exactly three things from you: your name, your mobile number, and your choice of service and time. The mobile number is the key to your booking — with the public tracking code you receive after booking you can check the status and cancel from any browser, with no account and no password.',
                    'If you register a business dashboard, we store your username and a cryptographically hashed password; your password never sits anywhere in plain text.',
                ],
            ],
            [
                'heading' => 'What we do with it',
                'body' => [
                    'Only what the product requires: showing and managing your appointment, sending the confirmation and reminder SMS before your slot, and aggregate statistics for the business owner (booking counts, busy hours) that never expose another customer’s personally identifying data.',
                ],
            ],
            [
                'heading' => 'Sharing with third parties',
                'body' => [
                    'If your appointment carries a fee, payment happens on the ZarinPal gateway site; your card number and banking details never touch Nobat’s servers — only the transaction outcome and its receipt are kept on our side.',
                    'Confirmation and reminder SMS are delivered through an SMS provider that receives only your name and mobile number for that one message. Nobat does not sell your data and does not use it for advertising.',
                ],
            ],
            [
                'heading' => 'Cookies',
                'body' => [
                    'Three essential cookies only: the login session (management dashboards), the CSRF token that keeps forms safe, and «nobat_locale» which remembers your preferred language. There are no tracking or advertising cookies.',
                ],
            ],
            [
                'heading' => 'Your rights',
                'body' => [
                    'You can cancel any upcoming appointment from the tracking page at any time. To have your data (name and mobile number) fully erased, ask the business you booked with — Nobat gives them complete tools over their customer data. Completed appointments remain as the business’s financial record.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Terms of Service',
        'updated' => 'Last updated: November 2026',

        'sections' => [
            [
                'heading' => 'Nobat’s role',
                'body' => [
                    'Nobat is an online booking platform where businesses (clinics, salons, studios and the like) publish their working calendar and services, and customers book slots. The business is solely responsible for the quality, pricing and staffing of the delivered service; Nobat is the booking intermediary, not the party performing it.',
                ],
            ],
            [
                'heading' => 'Booking and holds',
                'body' => [
                    'A recorded booking is held for you until the payment deadline (for paid services); if payment is not completed by then, the slot is released automatically so someone else can take it. Duplicate or bad-faith bookings can be cancelled by the business.',
                ],
            ],
            [
                'heading' => 'Cancellation and changes',
                'body' => [
                    'Cancel from the tracking page using the booking’s public code and the phone number you booked with. Rescheduling means cancelling and booking again. If something unavoidable comes up on the business’s side, they can change or cancel your slot from their dashboard and you will be notified by SMS.',
                ],
            ],
            [
                'heading' => 'Payments and refunds',
                'body' => [
                    'Payments run through the ZarinPal gateway and every transaction gets a numbered invoice you can open. If a paid appointment is cancelled, the refund follows the business’s stated policy and is processed through the same gateway.',
                ],
            ],
            [
                'heading' => 'Acceptable use',
                'body' => [
                    'Booking with fake information, spam bookings, attempts to bypass security limits, or any use that endangers the service for others is prohibited and leads to booking cancellation and access being blocked.',
                ],
            ],
        ],
    ],
];
