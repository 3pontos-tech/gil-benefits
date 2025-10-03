<?php

namespace TresPontosTech\Vouchers\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class VouchersServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->discoverResourcesForPanel('vouchers', FilamentPanel::Admin);
            }

            if ($panel->getId() === 'company') {
                $panel->discoverResourcesForPanel('vouchers', FilamentPanel::Company);
            }
        });
    }
}
