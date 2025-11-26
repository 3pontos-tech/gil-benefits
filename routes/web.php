<?php

use App\Filament\Guest\Pages\PartnerRegistrationPage;
use Illuminate\Support\Facades\Route;

// Partner registration route with rate limiting
Route::get('/partners', PartnerRegistrationPage::class)
    ->name('partners.register')
    ->middleware(['throttle:partner-registration']);