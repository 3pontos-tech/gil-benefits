<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Auth\LoginController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Auth\LogoutController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\AvatarController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\MeController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\PasswordController;
use TresPontosTech\Api\Http\Middleware\EnsureAppAccess;
use TresPontosTech\Api\Http\Middleware\ForceJsonAndLocale;

Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware([ForceJsonAndLocale::class, 'throttle:api-employee', SubstituteBindings::class])
    ->group(function (): void {
        Route::post('auth/login', LoginController::class)
            ->withoutMiddleware('throttle:api-employee')
            ->middleware('throttle:api-login')
            ->name('auth.login');

        Route::middleware(['auth:sanctum', 'ability:employee', EnsureAppAccess::class])->group(function (): void {
            Route::post('auth/logout', LogoutController::class)->name('auth.logout');

            Route::get('me', [MeController::class, 'show'])->name('me.show');
            Route::patch('me', [MeController::class, 'update'])->name('me.update');
            Route::put('me/password', PasswordController::class)->name('me.password');
            Route::post('me/avatar', [AvatarController::class, 'store'])->name('me.avatar.store');
            Route::delete('me/avatar', [AvatarController::class, 'destroy'])->name('me.avatar.destroy');
        });
    });
