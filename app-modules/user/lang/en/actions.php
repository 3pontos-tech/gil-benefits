<?php

declare(strict_types=1);

return [
    'import_users' => [
        'label' => 'Import Employees',
        'file' => [
            'label' => 'Spreadsheet (CSV or XLSX)',
            'helper_text' => 'Required columns: name (employee name), email (employee email), tax_id (employee CPF). Optional: phone_number (employee mobile number), document_id (employee RG), department (name of a department already registered for the company; the template lists them in the Departamentos sheet).',
            'helper_rules' => 'Active employees of the company and repeated rows (same e-mail or CPF as an earlier row) are skipped. Inactive employees of the company are reactivated. People who already have an :app account without a company are linked, with no new account and no password change. People at another company or whose account was deleted are not imported: contact support about them.',
            'helper_note' => 'Note: the template contains a row with fictional data that must be deleted before filling in the actual employee data.',
            'hint_action' => 'Download Template',
        ],
        'notifications' => [
            'empty' => [
                'title' => 'No users imported',
                'body' => 'The spreadsheet is empty or all rows were skipped.',
            ],
            'none_joining' => [
                'title' => 'No users imported',
                'body' => 'No row of the spreadsheet joins the company. See the reason for each one.',
            ],
            'started' => [
                'title' => 'Import in progress',
                'body' => 'You will receive a notification when the process is complete.',
            ],
        ],
        'outcomes' => [
            'create' => 'Created.',
            'link' => 'Already had an :app account and was linked to the company.',
            'reactivate' => 'Inactive employee of the company, reactivated.',
            'duplicate_in_sheet' => 'Skipped: e-mail or CPF repeated from an earlier row of the spreadsheet.',
            'already_member' => 'Skipped: already part of the company.',
            'employed_elsewhere' => 'Not imported: this person belongs to another company. Contact support.',
            'deleted_account' => "Not imported: this person's account was deleted. Contact support.",
            'conflicting_data' => 'Not imported: the e-mail and the CPF belong to different accounts. Contact support.',
        ],
        'summary' => [
            'imported' => ':count imported|:count imported',
            'linked' => ':count linked|:count linked',
            'reactivated' => ':count reactivated|:count reactivated',
            'skipped' => ':count skipped|:count skipped',
            'needs_support' => ':count needs support|:count need support',
            'none_joined' => 'Nobody joined the company: :parts.',
        ],
        'result_modal' => [
            'title' => 'Import Result',
        ],
    ],
];
