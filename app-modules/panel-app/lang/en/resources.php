<?php

declare(strict_types=1);

return [
    'appointments' => [
        'table' => [
            'category_type' => 'Appointment Type',
        ],
        'records' => [
            'view' => [
                'label' => 'View Record',
                'modal_heading' => 'Appointment record',
                'close' => 'Close',
            ],
        ],
        'feedback' => [
            'action_label' => 'Rate',
            'modal_heading' => 'Rate your consultation',
            'modal_description' => 'Your feedback helps us improve our service.',
            'rating' => 'Rating',
            'comment' => 'Comment (optional)',
            'submit' => 'Submit feedback',
            'submitted' => 'Feedback submitted successfully!',
        ],
        'pages' => [
            'create' => [
                'cannot_book_now' => 'Cannot book now',
                'no_appointments_available' => 'You have no available appointments this month or you already have an ongoing consultation. Complete the previous one to book another.',
                'book_appointment' => 'Book Appointment',
                'booked_successfully' => 'Appointment booked successfully',
                'booking_failed' => 'Failed to book appointment',
            ],
        ],
    ],
    'documents' => [
        'table' => [
            'title' => 'Document Type',
            'extension_type' => 'Extension Type',
            'active' => 'Is Active',
            'consultant' => 'Consultant',
            'created_at' => 'Sent At',
        ],
    ],
];
