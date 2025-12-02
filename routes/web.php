<?php

use App\Filament\Guest\Pages\PartnerRegistrationPage;
use Illuminate\Support\Facades\Route;

// Partner registration route with comprehensive rate limiting
Route::get('/partners', PartnerRegistrationPage::class)
    ->name('partners.register')
    ->middleware(['throttle:partner-registration', 'throttle:guest']);

// Additional security routes that might be needed
Route::middleware(['throttle:auth'])->group(function () {
    // Authentication routes would go here if not handled by Filament
    // Route::post('/login', [AuthController::class, 'login'])->name('login');
    // Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::middleware(['throttle:password-reset'])->group(function () {
    // Password reset routes would go here if not handled by Filament
    // Route::post('/password/email', [PasswordResetController::class, 'sendResetLinkEmail']);
    // Route::post('/password/reset', [PasswordResetController::class, 'reset']);
});
