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

        'notifications' => [
            'cancelled' => [
                'title' => 'Appointment Cancelled!',
                'body' => 'Your appointment has been cancelled. Please check your dashboard for details.',
            ],
            'drafted' => [
                'title' => 'Appointment Drafted',
                'body' => 'Your appointment has been drafted. Soon we will contact you to confirm your appointment.',
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
