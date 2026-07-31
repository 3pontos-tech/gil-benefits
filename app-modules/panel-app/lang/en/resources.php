<?php

declare(strict_types=1);

return [
    'appointments' => [
        'table' => [
            'category_type' => 'Appointment Type',
        ],
        'cancel' => [
            'action_label' => 'Cancel',
            'modal_heading' => 'Cancel your appointment?',
            'modal_description' => 'Once confirmed, the appointment is cancelled and cannot be restored automatically.',
            'notice_keeps_credit' => 'You can cancel up to :hours hours before the scheduled time. Cancel within that window and your credit is returned.',
            'notice_loses_credit' => 'Less than :hours hours remain before the scheduled time. Cancelling now will not return your credit.',
            'modal_submit_label' => 'Confirm',
            'modal_cancel_label' => 'Cancel',
            'success' => 'Appointment cancelled successfully.',
            'confirmed' => [
                'heading' => 'Cancellation confirmed!',
                'description' => 'Your appointment was cancelled successfully. Where applicable, the credit will be refunded according to the cancellation rules.',
                'appointment_cancelled' => 'Appointment cancelled.',
                'credit_processing' => 'Credit processing',
                'finish' => 'Finish',
            ],
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
                'back_to_list' => 'Back to list',
            ],
        ],
    ],
    'credits' => [
        'navigation_label' => 'My Credits',
        'title' => 'My Credits',
        'columns' => [
            'status' => 'Status',
            'distributed_at' => 'Distributed At',
            'purchased_at' => 'Purchased At',
        ],
    ],
    'documents' => [
        'tabs' => [
            'shared' => 'Shared with me',
            'mine' => 'My Documents',
        ],
        'table' => [
            'title' => 'Document Type',
            'extension_type' => 'Extension Type',
            'active' => 'Is Active',
            'consultant' => 'Consultant',
            'created_at' => 'Sent At',
        ],
        'form' => [
            'heading' => 'New Document',
            'title' => 'Document Name',
            'active' => 'Active',
            'files' => 'File',
        ],
    ],
];
