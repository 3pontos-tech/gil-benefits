<?php

use App\Filament\Guest\Pages\PartnerRegistrationPage;
use Illuminate\Support\Facades\Route;

// Partner registration route
Route::get('/partners', PartnerRegistrationPage::class)->name('partners.register');