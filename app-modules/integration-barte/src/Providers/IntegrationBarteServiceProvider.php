<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationBarte\Providers;

use Illuminate\Support\ServiceProvider;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\IntegrationBarte\BarteAdapter;
use TresPontosTech\IntegrationBarte\BarteClient;
use TresPontosTech\IntegrationBarte\Commands\SyncBartePlans;

class IntegrationBarteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([SyncBartePlans::class]);

        $this->loadRoutesFrom(__DIR__ . '/../../routes/integration-barte-routes.php');

        $this->registerBillingDriver();
    }

    private function registerBillingDriver(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(BillingManager::class)->extend(
                BillingProviderEnum::Barte->value,
                fn (): BillingContract => new BarteAdapter($this->app->make(BarteClient::class)),
            );
        });
    }
}
