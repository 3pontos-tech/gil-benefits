<?php

namespace TresPontosTech\Appointments\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class AppointmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register any bindings or singletons here
    }

    public function boot(): void
    {
        $this->loadTranslations();
        $this->registerFilamentResources();
    }

    private function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'appointments');
    }

    private function registerFilamentResources(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === FilamentPanel::Admin->value) {
                $panel->discoverResourcesForPanel('appointments', FilamentPanel::Admin);
            }

            if ($panel->getId() === FilamentPanel::User->value) {
                $panel->discoverResourcesForPanel('appointments', FilamentPanel::User);
            }
        });
    }
}
