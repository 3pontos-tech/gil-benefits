<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\Filament\CompanyPanelProvider;
use App\Providers\Filament\ConsultantPanelProvider;
use App\Providers\Filament\GuestPanelProvider;
use App\Providers\FilamentServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
    FilamentServiceProvider::class,
    CompanyPanelProvider::class,
    ConsultantPanelProvider::class,
    GuestPanelProvider::class,
];
