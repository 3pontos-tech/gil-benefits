<?php

declare(strict_types=1);

return [
    'support_tickets' => [
        'navigation_label' => 'Tickets',
        'model_label' => 'Ticket',
        'plural_model_label' => 'Tickets',

        'sections' => [
            'requester' => 'Requester',
            'classification' => 'Classification',
            'details' => 'Details',
            'attachments' => 'Attachments',
            'metadata' => 'Technical information',
        ],

        'columns' => [
            'protocol' => 'Protocol',
            'channel' => 'Channel',
            'category' => 'Category',
            'subject' => 'Subject',
            'status' => 'Status',
            'requester' => 'Requester',
            'created_at' => 'Opened at',
            'updated_at' => 'Updated at',
        ],

        'fields' => [
            'protocol' => 'Protocol',
            'channel' => 'Channel',
            'reopen_reason' => 'Reopen reason',
            'requester_name' => 'Name',
            'requester_email' => 'Email',
            'visitor_company_name' => 'Company',
            'category' => 'Category',
            'subject' => 'Subject',
            'description' => 'Description',
            'status' => 'Status',
            'attachment' => 'Attachment',
            'url' => 'Origin URL',
            'browser' => 'Browser',
            'device' => 'Device',
            'environment' => 'Environment',
            'created_at' => 'Opened at',
            'updated_at' => 'Updated at',
        ],

        'filters' => [
            'reopened' => 'Reopened only',
        ],

        'actions' => [
            'create' => 'Open ticket',
            'close' => 'Close',
            'update_status' => 'Change status',
        ],

        'notifications' => [
            'created' => 'Ticket :protocol created successfully!',
            'closed' => 'Ticket closed.',
            'status_updated' => 'Status updated.',
        ],

        'empty_state' => [
            'heading' => 'No tickets found',
            'description' => "You haven't opened any tickets yet.",
        ],

        'destinations' => [
            'title' => 'Destinations',
            'columns' => [
                'type' => 'Type',
                'channel' => 'Channel',
                'status' => 'Status',
                'reference_id' => 'Reference',
                'created_at' => 'Created at',
            ],
            'empty' => 'No destinations for this ticket.',
        ],
        'sent_notifications' => [
            'title' => 'Notifications',
            'columns' => [
                'event' => 'Event',
                'transition' => 'Ticket status',
                'status' => 'Delivery',
                'attempts' => 'Attempts',
                'response_code' => 'HTTP',
                'error' => 'Error',
                'message' => 'Message',
                'sent_at' => 'Sent at',
                'created_at' => 'Created at',
            ],
            'empty' => 'No notifications for this ticket.',
            'actions' => [
                'resend' => [
                    'label' => 'Resend',
                    'notification' => 'Notification queued again.',
                ],
            ],
        ],
        'status_history' => [
            'title' => 'History',
            'columns' => [
                'created_at' => 'When',
                'transition' => 'Status',
                'source' => 'Changed by',
                'actor' => 'Author',
                'reason' => 'Reason',
            ],
            'empty' => 'No status change recorded for this ticket.',
        ],
    ],
];
