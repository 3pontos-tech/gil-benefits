<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\User\Http\Controllers\DownloadImportTemplateController;

Route::get('/users/import-template', DownloadImportTemplateController::class)
    ->name('users.import-template.download');
