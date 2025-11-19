<?php

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
            ],
            'actions' => [
                'view' => 'View',
                'edit' => 'Edit',
                'delete_selected' => 'Delete selected',
            ],
        ],

        'wizard' => [
            'steps' => [
                'consultant' => 'Consultant',
                'pick_datetime' => 'Pick Date & Time',
                'review_confirm' => 'Review & Confirm',
            ],
            'labels' => [
                'choose_consultant' => 'Choose your consultant',
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
