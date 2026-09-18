<?php

declare(strict_types=1);

return [
    'pdf' => [
        'ready_title' => 'Voucher PDF ready',
        'ready_body' => 'The cards for batch :batch are ready to print.',
        'download' => 'Download PDF',
        'generate' => 'Generate PDF',
        'queued_title' => 'Building the PDF',
        'queued_body' => 'We will let you know in the notification centre as soon as it is ready to download.',
        'failed_title' => 'Could not generate the PDF',
        'failed_body' => 'Try again. If it keeps failing, let the engineering team know.',
    ],

    'errors' => [
        'code_not_found' => 'Invalid voucher code.',
        'code_exhausted' => 'This code has already been used.',
        'already_redeemed' => 'You have already redeemed this code.',
        'batch_expired' => 'This code is past its redemption window.',
        'program_inactive' => 'This partner company program is no longer active.',
        'already_holds_voucher' => 'You already have a voucher consultancy to use.',
    ],
];
