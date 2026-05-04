<?php

declare(strict_types=1);

return [
    'scheduled' => [
        'subject' => 'New appointment scheduled',
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
    'no_consultant' => 'unassigned consultant',
];
