<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\MeController;
use TresPontosTech\Api\Http\Middleware\EnsureAppAccess;
use TresPontosTech\Api\Http\Middleware\ForceJsonAndLocale;

Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware([ForceJsonAndLocale::class, 'throttle:api-employee', SubstituteBindings::class])
    ->group(function (): void {
        Route::middleware(['auth:sanctum', 'ability:employee', EnsureAppAccess::class])->group(function (): void {
            Route::get('me', [MeController::class, 'show'])->name('me.show');
        });
    });
