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
        'subject' => 'Appointment cancelled (less than :hours h notice)',
    ],
    'consultant_unassigned' => [
        'subject' => 'An appointment was removed from your agenda',
    ],
    'onboarding' => [
        'subject' => 'How to book your first session on :app',
    ],
    'no_consultant' => 'unassigned consultant',
];
