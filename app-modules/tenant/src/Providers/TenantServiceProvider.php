<?php

namespace TresPontosTech\Tenant\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
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
        // Tenant module doesn't have translations yet, but structure is ready
        // $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'tenant');
    }

    private function registerFilamentResources(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === FilamentPanel::Admin->value) {
                $panel->discoverResourcesForPanel('tenant', FilamentPanel::Admin);
            }

            if ($panel->getId() === FilamentPanel::Company->value) {
                $panel->discoverResourcesForPanel('tenant', FilamentPanel::Company);
            }
        });
    }
}
