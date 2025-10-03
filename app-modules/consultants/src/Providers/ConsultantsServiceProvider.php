<?php

namespace TresPontosTech\Consultants\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class ConsultantsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->discoverResourcesForPanel('consultants', FilamentPanel::Admin);
            }
        });
    }
}
