<?php

declare(strict_types=1);

return [
    'category' => [
        'login_access' => 'Login / Access',
        'platform_error' => 'Platform Error',
        'bug' => 'Confirmed Bug',
        'integration' => 'Integration',
        'performance' => 'Slowness / Performance',
        'scheduling_issue' => 'Scheduling Issue',
        'financial_issue' => 'Financial / Invoice / Refund',
        'contract_plan' => 'Contract / Plan / Upgrade',
        'cancellation_complaint' => 'Cancellation / Complaint',
        'suggestion_feedback' => 'Suggestion / Feedback',
        'general_question' => 'General Question',
        'other' => 'Other',
    ],
    'category_hint' => [
        'login_access' => [
            'password' => 'For login or access problems, you can reset your password yourself using the "Forgot my password" link on the login screen, without opening a ticket.',
            'plan' => 'If your password is correct and you still cannot access, check whether your company has cancelled its plan.',
        ],
        'login_access_profile_link' => 'Change my password in my profile',
        'scheduling_issue' => 'To reschedule, cancel the current appointment and create a new one on your preferred date. Cancellations made less than :hours hours in advance consume your monthly appointment or the credit used, with no refund. With :hours hours or more in advance, nothing is charged.',
    ],
    'ticket_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],
    'destination_channel' => [
        'support_ti' => 'IT Support',
        'financial' => 'Financial',
        'commercial' => 'Commercial',
        'cs' => 'CS',
        'product' => 'Product',
    ],
    'destination_type' => [
        'monday' => 'Monday',
        'email' => 'Email',
    ],
    'destination_status' => [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'failed' => 'Failed',
    ],
    'origin_source' => [
        'chatx' => 'ChatX (WhatsApp)',
    ],
];
