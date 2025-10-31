<?php

namespace TresPontosTech\Billing;

use Illuminate\Support\ServiceProvider;
use Override;
use TresPontosTech\Billing\Core\PlanRepository;
use TresPontosTech\Billing\Core\Repositories\ConfigPlanRepository;

class BillingServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(abstract: PlanRepository::class, concrete: ConfigPlanRepository::class);
    }
}
