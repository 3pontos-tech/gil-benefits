<?php

declare(strict_types=1);

return [
    'appointment_status' => [
        'pending' => 'Pending',
        'active' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'cancelled_late' => 'Cancelled (Late)',
        'no_show' => 'No-show',
    ],
    'appointment_history_action_type' => [
        'consultant_assigned' => 'Consultant assigned',
        'consultant_left' => 'Consultant removed',
        'consultant_changed' => 'Consultant changed',
        're_scheduled' => 'Rescheduled',
        'no_show_marked' => 'No-show recorded',
    ],
    'appointment_history_actor' => [
        'admin' => 'Administrator',
        'user' => 'Client',
        'consultant' => 'Consultant',
    ],
];
