<?php

declare(strict_types=1);

return [
    'heading' => 'Welcome, :name.',
    'subheading' => 'Manage your personal information, security and preferences.',

    'avatar' => [
        'heading' => 'Profile picture',
        'description' => 'This picture will be shown across the platform',
        'helper' => 'PNG, JPG or WEBP up to 5MB. Recommended: square image.',
    ],

    'tabs' => [
        'account' => 'Personal information',
        'financial' => 'Financial information',
    ],

    'personal' => [
        'heading' => 'Personal information',
        'description' => 'Keep your data up to date for a better experience.',
    ],

    'security' => [
        'heading' => 'Security',
        'description' => 'Change your password periodically to keep your account secure.',
    ],

    'financial' => [
        'heading' => 'Financial information',
        'description' => 'Review and keep your financial information up to date.',
    ],

    'summary' => [
        'heading' => 'Your account',
        'description' => 'A quick summary of your profile',
        'rows' => [
            'information' => 'Information',
            'email' => 'E-mail',
            'phone' => 'Phone',
            'last_updated' => 'Last updated',
        ],
        'status' => [
            'complete' => 'Up to date',
            'incomplete' => 'Incomplete',
            'verified' => 'Verified',
            'unverified' => 'Not verified',
            'filled' => 'Provided',
            'empty' => 'Not provided',
        ],
    ],

    'support' => [
        'heading' => 'Need help?',
        'description' => 'Our team is ready to assist you.',
        'action' => 'Contact support',
    ],

    'fields' => [
        'last_updated' => 'Last updated',
        'never_updated' => 'Never updated',
    ],

    'actions' => [
        'save' => 'Save changes',
        'cancel' => 'Back',
    ],

    'notifications' => [
        'saved' => 'Profile updated successfully!',
    ],
];
