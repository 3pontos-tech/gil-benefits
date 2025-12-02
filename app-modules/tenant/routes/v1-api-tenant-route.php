<?php

use Illuminate\Support\Facades\Route;
use TresPontosTech\Tenant\Http\Controllers\Api\v1\UsersController;

Route::prefix('api/v1/company')->group(function () {
    Route::post('{tenant}/users', [UsersController::class, 'store'])
        ->name('api.v1.company.users.store');
});
