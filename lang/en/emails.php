<?php

declare(strict_types=1);

return [
    'appointments' => [
        'scheduled' => [
            'title' => 'New Appointment Scheduled',
            'greeting' => 'Hello, **:name**!',
            'body' => 'You have a new confirmed appointment with **:name**.',
            'date_time' => 'Date and time:',
            'meeting_link' => 'Meeting link:',
            'meeting_link_label' => 'Join meeting',
            'employee_notes' => 'Employee notes:',
            'panel_description' => 'Access the panel to view all appointment details.',
            'button' => 'Access panel',
            'thanks' => 'Thank you',
        ],
        'completed' => [
            'title' => 'Appointment Completed',
            'greeting' => 'Hello, **:name**!',
            'body' => 'Your appointment with **:consultant**, held on **:date**, was successfully completed.',
            'feedback' => "We hope the session was helpful. If you'd like to leave feedback about the service, access the panel below.",
            'button' => 'Rate appointment',
            'help' => 'If you have any questions or need to schedule a new appointment, we are at your disposal.',
            'thanks' => 'Thank you',
        ],
        'cancelled' => [
            'title' => 'Appointment Cancelled',
            'greeting' => 'Hello, **:name**!',
            'body' => 'We inform you that your appointment with **:consultant**, scheduled for **:date**, has been cancelled.',
            'reschedule' => 'If the cancellation was made by mistake or if you wish to reschedule, access the panel and create a new request.',
            'button' => 'Access panel',
            'support' => 'If you have questions about the cancellation, please contact support.',
            'thanks' => 'Thank you',
        ],
        'cancelled_late' => [
            'title' => 'Appointment Cancelled (Late Notice)',
            'greeting' => 'Hello, **:name**!',
            'body' => 'Your appointment with **:consultant**, scheduled for **:date**, was cancelled with less than 24 hours notice.',
            'credit_notice' => 'Due to the late cancellation, the credit for this appointment **will not be returned** to your plan.',
            'reschedule' => 'If you wish to book a new appointment and still have available credits, access the panel.',
            'button' => 'Access panel',
            'support' => 'If you have any questions, please contact support.',
            'thanks' => 'Thank you',
        ],
    ],
    'users' => [
        'welcome' => [
            'title' => 'Welcome to :app!',
            'greeting' => 'Hello, **:name**!',
            'intro' => 'We are happy to have you with us. Your account was successfully created and you can now access your company\'s benefits platform.',
            'features_title' => 'What you can do',
            'feature_appointments' => 'Schedule appointments with specialized consultants',
            'feature_documents' => 'View documents shared by your team',
            'feature_history' => 'Track your service history',
            'admin_created' => 'Your access was created by your company administrator. Use the credentials below to log in for the first time:',
            'email_label' => '**Email:** :email',
            'temp_password' => '**Temporary password:** :password',
            'security_note' => 'For security, we recommend that you change your password after the first login.',
            'login_prompt' => 'To get started, access the platform with your login credentials.',
            'button' => 'Access platform',
            'help' => 'If you have any questions, feel free to contact us.',
            'thanks' => 'Thank you',
        ],
    ],
];
