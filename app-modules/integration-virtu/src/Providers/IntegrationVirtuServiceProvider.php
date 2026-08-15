<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Providers;

use Illuminate\Support\ServiceProvider;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\IntegrationVirtu\Commands\CreateVirtuPlanCommand;
use TresPontosTech\IntegrationVirtu\VirtuAdapter;
use TresPontosTech\IntegrationVirtu\VirtuClient;

class IntegrationVirtuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/virtu.php', 'virtu');
    }

    public function boot(): void
    {
        $this->commands([CreateVirtuPlanCommand::class]);

        $this->loadRoutesFrom(__DIR__ . '/../../routes/integration-virtu-routes.php');

        $this->registerBillingDriver();
    }

    /**
     * Registers the driver on the BillingManager rather than having billing
     * declare a createVirtuDriver() method — that keeps the billing module
     * unaware of any concrete gateway, so swapping one means adding or removing
     * a module.
     *
     * Deferred to booted() because BillingManager must already be bound as a
     * singleton by BillingServiceProvider: Manager::extend() stores the creator
     * on the instance, so a non-shared binding would drop it on the next
     * resolve() and the driver would silently go missing.
     */
    private function registerBillingDriver(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(BillingManager::class)->extend(
                BillingProviderEnum::Virtu->value,
                fn (): BillingContract => new VirtuAdapter($this->app->make(VirtuClient::class)),
            );
        });
    }
}
