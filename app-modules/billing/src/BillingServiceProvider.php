<?php

namespace TresPontosTech\Billing;

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Override;
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
        $this->registerBindings();
        $this->registerCommands();
    }

    public function boot(): void
    {
        $this->loadTranslations();
        $this->registerFilamentResources();
    }

    private function registerBindings(): void
    {
        Cashier::useSubscriptionModel(Subscription::class);
        Cashier::useSubscriptionItemModel(SubscriptionItem::class);

        $this->app->bind(abstract: PlanRepository::class, concrete: EloquentPlanRepository::class);
        $this->app->bind(abstract: WebhookController::class, concrete: SubscriptionWebhookController::class);
    }

    private function registerCommands(): void
    {
        $this->commands([
            SyncStripeResourcesCommand::class,
        ]);
    }

    private function loadTranslations(): void
    {
        // Billing module doesn't have translations yet, but structure is ready
        // $this->loadTranslationsFrom(__DIR__ . '/../lang', 'billing');
    }

    private function registerFilamentResources(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === FilamentPanel::Admin->value) {
                $panel->plugin(new AdminBillingPluginProvider);
            }

            if ($panel->getId() === FilamentPanel::Company->value) {
                Cashier::useCustomerModel(Company::class);
            }

            if ($panel->getId() === FilamentPanel::User->value) {
                Cashier::useCustomerModel(User::class);
            }
        });
    }
}
