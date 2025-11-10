<?php

namespace TresPontosTech\Appointments\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class AppointmentsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Load package translations for the appointments module
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'appointments');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->discoverResourcesForPanel('appointments', FilamentPanel::Admin);
            }

            if ($panel->getId() === 'app') {
                $panel->discoverResourcesForPanel('appointments', FilamentPanel::User);
            }
        });
    }
}
