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
    'reopened' => [
        'subject' => 'Ticket reopened — [:protocol] :subject',
        'preheader' => 'Ticket :protocol was reopened',
        'heading' => 'Ticket reopened',
        'labels' => [
            'category' => 'Category',
            'requester' => 'Requester',
            'reopened_by' => 'Reopened by',
            'reason' => 'Reopen reason:',
            'subject' => 'Subject:',
        ],
        'opened_at' => 'Opened on :date',
    ],
];
