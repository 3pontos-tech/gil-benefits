<?php

declare(strict_types=1);

return [
    'import_users' => [
        'label' => 'Import Employees',
        'file' => [
            'label' => 'Spreadsheet (CSV or XLSX)',
            'helper_text' => 'Required columns: name (employee name), email (employee email), tax_id (employee CPF), phone_number (employee mobile number). Optional: document_id (employee RG).',
            'helper_note' => 'Note: the template contains a row with fictional data that must be deleted before filling in the actual employee data.',
            'hint_action' => 'Download Template',
        ],
        'notifications' => [
            'empty' => [
                'title' => 'No users imported',
                'body' => 'The spreadsheet is empty or all rows were skipped.',
            ],
            'started' => [
                'title' => 'Import in progress',
                'body' => 'You will receive a notification when the process is complete.',
            ],
        ],
    ],
];
