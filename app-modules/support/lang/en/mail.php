<?php

declare(strict_types=1);

return [
    'confirmation' => [
        'subject' => 'Request received — :protocol',
    ],
    'status_updated' => [
        'subjects' => [
            'in_progress' => 'Your ticket is now in progress — :protocol',
            'resolved' => 'Your ticket has been resolved — :protocol',
            'closed' => 'Your ticket has been closed — :protocol',
        ],
        'intros' => [
            'in_progress' => 'We are on it! Your ticket is now **in progress** and our team is working on a solution.',
            'resolved' => 'Good news! Your ticket has been marked as **resolved**.',
            'closed' => 'Your ticket has been **closed**.',
        ],
    ],
];
