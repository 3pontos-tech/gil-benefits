<?php

declare(strict_types=1);

namespace TresPontosTech\Billing;

use App\Models\Users\User;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Override;
use TresPontosTech\Billing\Barte\Commands\SyncBartePlans;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Commands\SyncBillingCustomersCommand;
use TresPontosTech\Billing\Core\Commands\SyncStripeResourcesCommand;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Models\Subscriptions\SubscriptionItem;
use TresPontosTech\Billing\Core\Repositories\EloquentPlanRepository;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Billing\Stripe\Subscription\SubscriptionWebhookController;
use TresPontosTech\Company\Models\Company;

class BillingServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        Cashier::useSubscriptionModel(Subscription::class);
        Cashier::useSubscriptionItemModel(SubscriptionItem::class);

        $this->app->bind(abstract: PlanRepository::class, concrete: EloquentPlanRepository::class);
        $this->app->bind(abstract: WebhookController::class, concrete: SubscriptionWebhookController::class);

        // Shared on purpose: Manager memoises resolved drivers on the instance and
        // Manager::extend() stores custom creators there too. Resolving a fresh
        // BillingManager per call would throw both away, so a driver registered by
        // an integration module would go missing on the next resolve().
        $this->app->singleton(BillingManager::class);

        $this->commands([
            SyncStripeResourcesCommand::class,
            SyncBartePlans::class,
            SyncBillingCustomersCommand::class,
        ]);

        Panel::configureUsing(function (Panel $panel): void {

            if ($panel->getId() === 'company') {
                Cashier::useCustomerModel(Company::class);
            }

            if ($panel->getId() === 'app') {
                Cashier::useCustomerModel(User::class);
            }
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/billing-routes.php');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'billing');
    }
}
