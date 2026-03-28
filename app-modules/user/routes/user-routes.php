<?php

use Illuminate\Support\Facades\Route;

Route::get('/users/import-template', function () {
    return response()->streamDownload(function (): void {
        echo implode(',', ['name', 'email', 'phone_number', 'document_id', 'tax_id']) . "\n";
    }, 'template-import-users.csv');
})->name('users.import-template.download');
