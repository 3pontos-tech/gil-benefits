<?php

use Illuminate\Support\Facades\Route;
use TresPontosTech\Tenant\Http\Controllers\Api\v1\UsersController;
use TresPontosTech\Tenant\Http\Middleware\VerifyTenantTokenMiddleware;

Route::prefix('api/v1/company')->middleware(VerifyTenantTokenMiddleware::class)->group(function (): void {
    Route::post('{tenant}/users', [UsersController::class, 'store'])
        ->name('api.v1.company.users.store');

    Route::delete('{tenant}/users/{user}', [UsersController::class, 'destroy'])
        ->name('api.v1.company.users.delete');
});
