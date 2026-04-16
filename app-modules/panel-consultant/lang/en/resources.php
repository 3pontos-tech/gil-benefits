<?php

declare(strict_types=1);

return [
    'appointments' => [
        'infolist' => [
            'tabs' => [
                'consultation' => 'Consultation',
                'client' => 'Client',
                'prontuario' => 'Financial Profile',
            ],
            'consultation' => 'Consultation Details',
            'client' => 'About the Client',
            'client_email' => 'Email',
            'financial_profile' => 'Financial Profile',
            'financial_profile_description' => 'Profile filled in by the client before the consultation.',
            'no_anamnese' => 'Profile not filled in',
            'no_anamnese_description' => 'This client has not filled in their financial profile yet.',
            'meeting_url_pending' => 'Awaiting confirmation',
        ],
    ],
    'documents' => [
        'navigation_label' => 'Materials',
        'model_label' => 'Material',
        'plural_model_label' => 'Materials',
        'table' => [
            'title' => 'Document Title',
            'extension_type' => 'Type',
            'active' => 'Is Active',
        ],
        'form' => [
            'title' => 'Document Title',
            'extension_type' => 'Extension',
            'active' => 'Is Active',
            'heading' => 'New Document',
            'files' => 'File',
        ],
    ],
    'share_documents' => [
        'action' => [
            'label' => 'Share',
            'heading' => 'Share Document',
            'modal_description' => 'Only customers that do not have access to this document will be listed.',
            'form' => [
                'customer' => 'Customer',
            ],
        ],
        'relation_manager' => [
            'title' => 'Shared With',
            'table' => [
                'employee' => 'Employee',
                'shared_at' => 'Shared At',
                'active' => 'Active',
            ],
            'actions' => [
                'deactivate' => 'Deactivate',
                'activate' => 'Activate',
            ],
        ],
    ],
];
