<?php

namespace TresPontosTech\IntegrationHighlevel;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\ServiceProvider;

class IntegrationHighlevelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(): void
    {
        $this->loadTranslations();
        $this->registerHttpMacros();
        $this->registerFilamentResources();
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config-highlevel.php', 'highlevel');
    }

    private function loadTranslations(): void
    {
        // Integration HighLevel module doesn't have translations yet, but structure is ready
        // $this->loadTranslationsFrom(__DIR__ . '/../lang', 'integration-highlevel');
    }

    private function registerHttpMacros(): void
    {
        PendingRequest::macro('withLocation', fn () => $this->withQueryParameters([
            'locationId' => config('highlevel.location'),
        ]));

        PendingRequest::macro('withDefaultVersion', fn (?string $version = null) => $this->withHeader(
            'Version',
            $version ?? config('highlevel.version')
        ));

        PendingRequest::macro('withDefaultCompany', fn (?string $companyId = null) => $this->withQueryParameters(
            ['companyId' => $companyId ?? config('highlevel.company')]
        ));
    }

    private function registerFilamentResources(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === FilamentPanel::Admin->value) {
                $panel->discoverResourcesForPanel('integration-highlevel', FilamentPanel::Admin);
            }
        });
    }
}
