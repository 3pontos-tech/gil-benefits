<?php

declare(strict_types=1);

return [
    'confirmation' => [
        'subject' => 'Request received — :protocol',
    ],
    'status_updated' => [
        'subjects' => [
            'resolved' => 'Your ticket has been resolved — :protocol',
            'closed' => 'Your ticket has been closed — :protocol',
        ],
        'resolved_intro' => 'Good news! Your ticket has been marked as **resolved**.',
        'closed_intro' => 'Your ticket has been **closed**.',
    ],
];
