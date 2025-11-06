<?php

namespace TresPontosTech\Billing;

use App\Models\Users\User;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Override;
use TresPontosTech\Billing\Core\PlanRepository;
use TresPontosTech\Billing\Core\Repositories\ConfigPlanRepository;
use TresPontosTech\Billing\Stripe\Subscription\SubscriptionWebhookController;
use TresPontosTech\Company\Models\Company;

class BillingServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(abstract: PlanRepository::class, concrete: ConfigPlanRepository::class);
        $this->app->bind(abstract: WebhookController::class, concrete: SubscriptionWebhookController::class);

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'company') {
                Cashier::useCustomerModel(Company::class);
            }

            if ($panel->getId() === 'app') {
                Cashier::useCustomerModel(User::class);
            }
        });
    }

    public function boot(): void {}
}
