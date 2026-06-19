<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Support',

    'help_center' => [
        'navigation_label' => 'Open Ticket',
        'title' => 'Help Center',
        'section_visitor' => 'Your details',
        'section_category' => 'Request type',
        'section_details' => 'Ticket details',
        'fields' => [
            'visitor_name' => 'Name',
            'visitor_email' => 'Email',
            'visitor_company_name' => 'Company (optional)',
            'category' => 'Category',
            'subject' => 'Subject',
            'description' => 'Description',
            'attachment' => 'Attachment (optional)',
        ],
        'actions' => [
            'submit' => 'Submit ticket',
        ],
        'notifications' => [
            'created' => 'Ticket :protocol created successfully!',
            'rate_limited' => 'You have opened too many tickets. Please wait :seconds seconds and try again.',
        ],
    ],
];
