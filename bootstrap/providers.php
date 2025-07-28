<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\Filament\CompanyPanelProvider;
use App\Providers\Filament\ConsultantPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
    CompanyPanelProvider::class,
    ConsultantPanelProvider::class,
];
