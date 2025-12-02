<?php

namespace TresPontosTech\Company\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Company\Models\Company;

class CompanyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerMorphMap();
    }

    public function boot(): void
    {
        $this->loadTranslations();
        $this->registerFilamentResources();
    }

    private function registerMorphMap(): void
    {
        Relation::morphMap([
            'company' => Company::class,
        ]);
    }

    private function loadTranslations(): void
    {
        // Company module doesn't have translations yet, but structure is ready
        // $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'company');
    }

    private function registerFilamentResources(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === FilamentPanel::Admin->value) {
                $panel->discoverResourcesForPanel('company', FilamentPanel::Admin);
            }

            if ($panel->getId() === FilamentPanel::Company->value) {
                $panel->discoverResourcesForPanel('company', FilamentPanel::Company);
            }
        });
    }
}
