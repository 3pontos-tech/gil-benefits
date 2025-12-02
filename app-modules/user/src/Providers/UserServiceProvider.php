<?php

namespace TresPontosTech\User\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
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
        // User module doesn't have translations yet, but structure is ready
        // $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'user');
    }

    private function registerFilamentResources(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === FilamentPanel::User->value) {
                $panel->discoverResourcesForPanel('user', FilamentPanel::User);
            }
        });
    }
}
