<?php

namespace TresPontosTech\Billing;

use App\Models\Users\User;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Override;
use TresPontosTech\Billing\Core\Commands\SyncStripeResourcesCommand;
use TresPontosTech\Billing\Core\Filament\App\Widget\UserCurrentPlanWidget;
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

        $this->commands([
            SyncStripeResourcesCommand::class,
        ]);

        Panel::configureUsing(function (Panel $panel): void {

            if ($panel->getId() === 'admin') {
                $panel->plugin(new AdminBillingPluginProvider);
            }

            if ($panel->getId() === 'company') {
                Cashier::useCustomerModel(Company::class);
            }

            if ($panel->getId() === 'app') {
                Cashier::useCustomerModel(User::class);
                $panel->widgets([
                    UserCurrentPlanWidget::class,
                ]);
            }
        });
    }

    public function boot(): void {}
}
