<?php

declare(strict_types=1);

return [
    'scheduled' => [
        'subject' => 'New appointment scheduled',
    ],
    'requested_admin' => [
        'subject' => 'New appointment request awaiting assignment',
    ],
    'completed' => [
        'subject' => 'Your appointment has been completed',
    ],
    'cancelled' => [
        'subject' => 'Appointment cancelled',
    ],
    'user_cancelled_late' => [
        'subject' => 'Appointment cancelled (less than 24h notice)',
    ],
    'consultant_unassigned' => [
        'subject' => 'An appointment was removed from your agenda',
    ],
    'no_consultant' => 'unassigned consultant',
];
