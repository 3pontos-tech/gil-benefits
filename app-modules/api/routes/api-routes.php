<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments\AppointmentController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments\FeedbackController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments\SlotController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Auth\LoginController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Auth\LogoutController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Credits\CreditsController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Materials\MaterialController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Materials\MaterialStateController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\AnamneseController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\AvatarController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\JourneyController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\MeController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Me\PasswordController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Notifications\NotificationController;
use TresPontosTech\Api\Http\Controllers\V1\Employee\Tickets\TicketController;
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
            Route::get('me/journey', JourneyController::class)->name('me.journey');
            Route::get('me/anamnese', [AnamneseController::class, 'show'])->name('me.anamnese.show');
            Route::put('me/anamnese', [AnamneseController::class, 'update'])->name('me.anamnese.update');

            Route::get('appointments/slots', SlotController::class)->name('appointments.slots');
            Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
            Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
            Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->whereUuid('appointment')->name('appointments.show');
            Route::patch('appointments/{appointment}', [AppointmentController::class, 'update'])->whereUuid('appointment')->name('appointments.update');
            Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->whereUuid('appointment')->name('appointments.destroy');
            Route::post('appointments/{appointment}/feedback', FeedbackController::class)->whereUuid('appointment')->name('appointments.feedback.store');

            Route::get('credits', CreditsController::class)->name('credits.show');

            Route::get('materials', [MaterialController::class, 'index'])->name('materials.index');
            Route::post('materials', [MaterialController::class, 'store'])->name('materials.store');
            Route::delete('materials/{document}', [MaterialController::class, 'destroy'])->whereUuid('document')->name('materials.destroy');
            Route::patch('materials/{document}/favorite', [MaterialStateController::class, 'favorite'])->whereUuid('document')->name('materials.favorite');
            Route::patch('materials/{document}/viewed', [MaterialStateController::class, 'viewed'])->whereUuid('document')->name('materials.viewed');

            Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('notifications.read');

            Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
            Route::patch('tickets/{ticket}', [TicketController::class, 'update'])->whereUuid('ticket')->name('tickets.update');
        });
    });
