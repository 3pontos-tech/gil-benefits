<?php

declare(strict_types=1);

return [
    'appointments' => [
        'table' => [
            'title' => 'My Appointments',
            'heading' => 'Booked consultations',
            'description' => 'Manage and follow your booked consultations.',
            'category_type' => 'Appointment Type',
            'filters' => [
                'status' => 'Status',
                'category_type' => 'Appointment type',
            ],
        ],
        'cancel' => [
            'action_label' => 'Cancel',
            'modal_heading' => 'Cancel your appointment?',
            'modal_description' => 'Once confirmed, the appointment is cancelled and cannot be restored automatically.',
            'notice_keeps_credit' => 'You can cancel up to :hours hours before the scheduled time. Cancel within that window and your credit is returned.',
            'notice_loses_credit' => 'Less than :hours hours remain before the scheduled time. Cancelling now will not return your credit.',
            'modal_submit_label' => 'Confirm',
            'modal_cancel_label' => 'Cancel',
            'confirmed' => [
                'heading' => 'Cancellation confirmed!',
                'description' => 'Your appointment was cancelled successfully. Where applicable, the credit will be refunded according to the cancellation rules.',
                'appointment_cancelled' => 'Appointment cancelled.',
                'credit_processing' => 'Credit processing',
                'finish' => 'Finish',
            ],
        ],
        'schedule' => [
            'action_label' => 'New appointment',
            'next' => 'Next',
            'category' => [
                'heading' => "Let's book your consultation?",
                'description' => 'Choose the category of your appointment.',
            ],
            'slot' => [
                'heading' => 'Pick date and time',
                'description' => 'Select a date and time for your consultation from the available options.',
                'available_times' => 'Available Times',
                'pick_date_first' => 'Select a date to see the available times.',
                'no_slots' => 'No times available for this date.',
            ],
            'review' => [
                'heading' => 'Review and confirm',
                'description' => 'Review the booking details before confirming your consultation.',
                'category' => 'Category',
                'date' => 'Date',
                'time' => 'Time',
                'duration' => 'Duration',
                'duration_value' => '60 minutes',
                'notice' => 'You can reschedule your consultation up to :hours hours before the scheduled time.',
                'submit' => 'Book consultation',
            ],
            'confirmed' => [
                'heading' => 'Booking confirmed!',
                'description' => 'Your consultation was booked successfully. Your appointment is confirmed.',
                'category_caption' => 'Consultation category',
                'awaiting_confirmation' => 'Awaiting confirmation',
                'back_home' => 'Back to home page',
            ],
        ],
        'reschedule' => [
            'action_label' => 'Reschedule',
            'modal_heading' => 'Reschedule Appointment',
            'modal_description' => 'Pick a new date and time. If your consultant is not available at the chosen time, the appointment goes back to the assignment queue.',
            'modal_submit_label' => 'Confirm new time',
            'success' => 'Appointment rescheduled successfully.',
            'success_body_kept_consultant' => 'Your consultant is available and has been kept.',
            'success_body_unassigned' => 'Your consultant was not available at this time. We will assign a new one and let you know shortly.',
            'calendar_sync_failed' => 'The appointment was rescheduled, but the consultant calendar may be out of sync.',
            'next' => 'Next',
            'cannot_reschedule' => 'This appointment can no longer be rescheduled.',
            'slot_unavailable' => 'The chosen time is no longer available. Pick another time.',
            'failed' => 'Failed to reschedule the consultation.',
            'intro' => [
                'heading' => "Let's reschedule your consultation?",
                'description' => 'You can reschedule your consultation up to :hours hours before the scheduled time.',
                'keeps_current_slot' => 'Your current time is kept until the new booking is confirmed',
            ],
            'slot' => [
                'heading' => 'Pick a new date and time',
                'description' => 'Select a new date and time for your consultation from the available options.',
            ],
            'review' => [
                'heading' => 'Change summary',
                'description' => 'Review the rescheduling details before confirming the change to your consultation.',
                'category' => 'Category',
                'new_date' => 'New date',
                'new_time' => 'New time',
                'duration' => 'Duration',
                'duration_value' => '60 minutes',
                'notice' => 'Your current time will be replaced after confirmation.',
                'submit' => 'Confirm',
            ],
            'confirmed' => [
                'heading' => 'Rescheduling confirmed!',
                'description' => 'Your consultation was rescheduled successfully. The new booking is confirmed.',
                'before' => 'Before',
                'now' => 'Now',
                'awaiting_confirmation' => 'Awaiting confirmation',
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
                'book_appointment' => 'Book Appointment',
                'booked_successfully' => 'Appointment booked successfully',
                'booking_failed' => 'Failed to book appointment',
                'back_to_list' => 'Back to list',
            ],
        ],
    ],
    'credits' => [
        'navigation_label' => 'My Credits',
        'title' => 'My credits',
        'history' => [
            'heading' => 'Credit history',
            'description' => 'Review your credit and distribution history.',
            'purchase' => 'Buy credits',
        ],
        'columns' => [
            'status' => 'Status',
            'distributed_to' => 'Distributed to',
            'purchased_at' => 'Purchase date',
            'date' => 'Date',
        ],
    ],
    'documents' => [
        'my_materials' => [
            'heading' => 'My materials',
            'description' => 'Access and follow every material available to you.',
            'new_document' => 'New document',
        ],
        'shared' => [
            'heading' => 'Shared with me',
            'description' => 'Access and follow every material available to you.',
        ],
        'actions' => [
            'access' => 'Access',
        ],
        'table' => [
            'title' => 'Document name',
            'extension_type' => 'Type',
            'active' => 'Is Active',
            'consultant' => 'Consultant',
            'created_at' => 'Sent at',
        ],
        'form' => [
            'heading' => 'New Document',
            'title' => 'Document Name',
            'active' => 'Active',
            'files' => 'File',
        ],
    ],
];
