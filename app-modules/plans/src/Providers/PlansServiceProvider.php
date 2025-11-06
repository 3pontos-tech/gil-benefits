<?php

namespace TresPontosTech\Plans\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class PlansServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->discoverResourcesForPanel('plans', FilamentPanel::Admin);
            }
        });
    }

    public function boot(): void {}
}
