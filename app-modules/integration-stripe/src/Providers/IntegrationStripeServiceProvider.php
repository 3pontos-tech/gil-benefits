<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\IntegrationStripe\Commands\CreateStripePlanCommand;
use TresPontosTech\IntegrationStripe\Commands\CreateStripeWebhookCommand;
use TresPontosTech\IntegrationStripe\StripeAdapter;
use TresPontosTech\IntegrationStripe\StripeApi;

class IntegrationStripeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/stripe.php', 'stripe');

        $this->app->bind(StripeApi::class, fn (): StripeApi => new StripeApi(
            new StripeClient(['api_key' => (string) config('stripe.secret')]),
        ));
    }

    public function boot(): void
    {
        $this->commands([CreateStripePlanCommand::class, CreateStripeWebhookCommand::class]);

        $this->loadRoutesFrom(__DIR__ . '/../../routes/integration-stripe-routes.php');

        $this->registerBillingDriver();
    }

    /**
     * Deferred to booted() because BillingManager must already be bound as a singleton by
     * BillingServiceProvider: Manager::extend() stores the creator on the instance.
     */
    private function registerBillingDriver(): void
    {
        $this->app->booted(function (): void {
            $container = $this->app;

            $this->app->make(BillingManager::class)->extend(
                BillingProviderEnum::Stripe->value,
                fn (): BillingContract => new StripeAdapter($container->make(StripeApi::class)),
            );
        });
    }
}
