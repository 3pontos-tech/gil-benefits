<?php

declare(strict_types=1);

return [
    'api_url' => env('MONDAY_API_URL', 'https://api.monday.com/v2'),
    'token' => env('MONDAY_API_TOKEN'),
    'webhook_secret' => env('MONDAY_WEBHOOK_SECRET'),

    // Support ticket board. board_id/group_id/column ids are passed to the
    // (generic) client per call — they live here, not inside the client.
    'board_id' => env('MONDAY_BOARD_ID'),
    'group_id' => env('MONDAY_GROUP_ID', 'topics'),
    'columns' => [
        'status' => env('MONDAY_COLUMN_STATUS', 'status'),
        'protocol' => env('MONDAY_COLUMN_PROTOCOL', 'text_protocol'),
        'category' => env('MONDAY_COLUMN_CATEGORY', 'text_category'),
        'requester' => env('MONDAY_COLUMN_REQUESTER', 'text_requester'),
        'description' => env('MONDAY_COLUMN_DESCRIPTION', 'long_text_description'),
        'attachments' => env('MONDAY_COLUMN_ATTACHMENTS', 'file_attachments'),
        'created_at' => env('MONDAY_COLUMN_CREATED_AT', 'date_created_at'),
        'updated_at' => env('MONDAY_COLUMN_UPDATED_AT', 'date_updated_at'),
    ],

    // Monday status label INDEX <-> SupportTicketStatusEnum value. Indexes are
    // stable identifiers on the board — they survive label renames, casing and
    // locale changes (the label text does not; Monday matches it exactly).
    'status_indexes' => [
        'pending' => (int) env('MONDAY_STATUS_INDEX_PENDING', 17),
        'in_progress' => (int) env('MONDAY_STATUS_INDEX_IN_PROGRESS', 0),
        'resolved' => (int) env('MONDAY_STATUS_INDEX_RESOLVED', 2),
        'closed' => (int) env('MONDAY_STATUS_INDEX_CLOSED', 3),
    ],
];
