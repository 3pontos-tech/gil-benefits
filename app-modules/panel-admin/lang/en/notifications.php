<?php

declare(strict_types=1);

return [
    'user_registered' => [
        'title' => 'New user registered',
        'body' => ':name just registered in the system.',
    ],
    'appointment_booked' => [
        'title' => 'New appointment',
        'body' => ':name booked a new appointment.',
    ],
    'appointment_cancelled' => [
        'title' => 'Appointment cancelled',
        'body' => ":name's appointment was cancelled.",
    ],
    'appointment_completed' => [
        'title' => 'Appointment completed',
        'body' => ":name's appointment was completed.",
    ],
    'stripe_webhook_failed' => [
        'title' => 'Stripe webhook failed',
        'body' => 'Stripe event :type (:event_id) was not processed after every retry, and Stripe will not send it again. Re-run the job with queue:retry.',
    ],
    'unknown_user' => 'Unknown user',
];
