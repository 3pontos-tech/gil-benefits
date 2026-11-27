<?php

declare(strict_types=1);

namespace TresPontosTech\Billing;

use Illuminate\Support\ServiceProvider;
use Override;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Repositories\EloquentPlanRepository;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;

class BillingServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(abstract: PlanRepository::class, concrete: EloquentPlanRepository::class);

        // Shared on purpose: Manager memoises resolved drivers on the instance and
        // Manager::extend() stores custom creators there too. Resolving a fresh
        // BillingManager per call would throw both away, so a driver registered by
        // an integration module would go missing on the next resolve().
        $this->app->singleton(BillingManager::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'billing');
        $this->mergeConfigFrom(__DIR__ . '/../config/billing.php', 'billing');
    }
}
