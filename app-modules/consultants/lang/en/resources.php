<?php

declare(strict_types=1);

return [
    'schedules' => [
        'title' => 'Schedules',

        'table' => [
            'columns' => [
                'name' => 'Name',
                'type' => 'Type',
                'days' => 'Days',
                'periods' => 'Periods',
                'active' => 'Active',
            ],
        ],

        'actions' => [
            'add_availability' => 'Add Availability',
            'add_blocked' => 'Add Blocked Time',
        ],

        'form' => [
            'name' => 'Name',
            'days_of_week' => 'Days of Week',
            'time_periods' => 'Time Periods',
            'time_periods_blocked' => 'Time Periods (leave empty to block full day)',
            'start' => 'Start',
            'end' => 'End',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'placeholder_name_availability' => 'e.g. Office Hours',
            'placeholder_name_blocked' => 'e.g. Vacation, Holiday',
            'placeholder_end_date' => 'Leave empty for single day',
        ],

        'days' => [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ],
    ],

    'materials' => [
        'exceptions' => [
            'not_uploaded_by_you' => 'You can only remove the materials you uploaded.',
        ],
    ],

    // Aviso e push do material compartilhado (#349): sem o título do material, que pode revelar
    // a situação financeira da pessoa na tela de bloqueio.
    'notifications' => [
        'material_shared' => [
            'title' => 'New material from your consultant',
            'body' => 'Your consultant shared a material with you. Tap to open.',
        ],
    ],
];
