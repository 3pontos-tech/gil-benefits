<?php

declare(strict_types=1);

return [
    'appointments' => [
        'label' => 'Appointment',
        'plural' => 'Appointments',
        'navigation' => 'Appointments',

        'table' => [
            'columns' => [
                'consultant' => 'Consultant',
                'user' => 'User',
                'appointment_at' => 'Date & Time',
                'status' => 'Status',
                'created_at' => 'Created at',
                'updated_at' => 'Updated at',
                'from' => 'From',
                'until' => 'Until',
                'company' => 'Company',
            ],
            'actions' => [
                'view' => 'View',
                'edit' => 'Edit',
                'delete_selected' => 'Delete selected',
            ],
        ],

        'infolist' => [
            'metadata' => 'Metadata',
            'appointment_info' => 'Appointment Info',
            'anamnese' => 'User Profile',
            'ai_generation' => 'AI generation',
            'ai' => [
                'model_used' => 'Model used',
                'input_tokens' => 'Input tokens',
                'output_tokens' => 'Output tokens',
                'total_tokens' => 'Total tokens',
                'content' => 'Record',
                'internal_summary' => 'Internal summary',
                'published_at' => 'Published at',
                'draft' => 'Draft',
            ],
            'employee_documents' => 'Employee Documents',
            'employee_shared_documents' => 'Document shared with employee',
            'documents' => [
                'title' => 'Document Name',
                'type' => 'Type',
                'empty' => 'No documents shared with this employee.',
            ],
        ],

        'form' => [
            'meeting_url' => 'Meeting URL',
        ],

        'exceptions' => [
            'slot_unavailable' => 'This time slot is no longer available. Please select another.',
            'consultant_unavailable' => 'This consultant is not available for the selected time slot.',
            'calendar_event_failed' => 'Failed to create the Google Calendar event. Try saving again or check the integration.',
        ],

        'records' => [
            'editor_label' => 'Record (visible to the client after publishing)',
            'notifications' => [
                'ready' => [
                    'title' => 'Record ready for review',
                    'body' => 'Appointment: :user',
                ],
                'failed' => [
                    'title' => 'Failed to generate record',
                    'body' => [
                        'unreadable' => 'We could not read the uploaded document. Make sure it is not corrupted or password protected.',
                        'generation' => 'We could not generate the record. Try again in a few minutes or write it manually.',
                        'unexpected' => 'Unexpected error while generating the record. Try again or write it manually.',
                    ],
                ],
                'draft_saved' => [
                    'title' => 'Draft saved',
                ],
                'published' => [
                    'title' => 'Record published',
                ],
                'updated' => [
                    'title' => 'Record updated',
                ],
            ],
        ],

        'notifications' => [
            'cancelled' => [
                'title' => 'Appointment Cancelled!',
                'body' => 'Your appointment has been cancelled. Please check your dashboard for details.',
            ],
            'cancelled_by_user' => [
                'body' => ":name's appointment was cancelled by the user.",
            ],
            'cancelled_by_admin' => [
                'body' => ":name's appointment was cancelled by the administrator.",
            ],
            'user_cancelled_late' => [
                'title' => 'Appointment Cancelled',
                'body' => 'Your appointment has been cancelled. As it was cancelled less than 24 hours before the scheduled time, your credit has been consumed.',
            ],
            'pending' => [
                'title' => 'Appointment under Scheduling',
                'body' => 'We found a match for your appointment. We will contact you soon.',
            ],
            'scheduled' => [
                'title' => 'Appointment Scheduled!',
                'body' => 'Your appointment has been scheduled. Please check your dashboard for details.',
            ],
            'completed' => [
                'title' => 'Appointment Finished!',
                'body' => 'Your appointment has been completed. Please check your dashboard for details.',
            ],
        ],

        'wizard' => [
            'steps' => [
                'category_type' => 'Consulting Category',
                'pick_datetime' => 'Pick Date & Time',
                'review_confirm' => 'Review & Confirm',
            ],
            'labels' => [
                'category_type' => 'Select the consulting category',
                'date' => 'Date',
                'available_times' => 'Available Times',
                'duration' => 'Duration',
                'duration_default' => '60 minutes',
                'summary' => 'Summary',
                'notes' => 'Notes',
            ],
            'actions' => [
                'submit' => 'Start researching',
            ],
        ],
    ],
];
