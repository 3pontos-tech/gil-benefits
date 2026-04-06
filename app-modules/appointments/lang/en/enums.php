<?php

declare(strict_types=1);

return [
    'appointment_status' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'scheduling' => 'Scheduling',
        // Note: the "active" status is displayed as "Scheduled" in the UI
        'active' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
];
