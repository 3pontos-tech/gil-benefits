<?php

namespace TresPontosTech\Billing\Core\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Cashier\SubscriptionBuilder;
use Livewire\Attributes\Computed;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;

class TenantSubscriptionPage extends Page
{
    protected static ?string $slug = 'available-subscriptions';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::ScreenLarge;

    protected string $view = 'available-subscriptions';

    protected static bool $shouldRegisterNavigation = false;

    public string $selectedPlan = 'company';

    public int $seatsAmount = 5;

    protected function getViewData(): array
    {
        return [
            'plan' => $this->getActiveTenantPlan(),
        ];
    }

    #[Computed]
    public function getActiveTenantPlan(): PlanEntity
    {
        return app(PlanRepository::class)->getActiveTenantPlan();
    }

    public function checkout(): void
    {

        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $plan = $this->getActiveTenantPlan($this->selectedPlan);
        $price = $plan->prices->first();

        $seats = $this->seatsAmount;

        if ($seats < 5) {
            $seats = 5;
        }

        $sessionCheckout = $tenant
            ->newSubscription(type: $plan->slug)
            ->when(
                value: $plan->isMeteredPrice,
                callback: static fn (SubscriptionBuilder $subscription): SubscriptionBuilder => $subscription
                    ->meteredPrice($price->priceId)
                    ->quantity($seats),
            )
            ->when(
                value: $plan->hasGenericTrial && $plan->trialDays !== false,
                callback: static fn (SubscriptionBuilder $subscription): SubscriptionBuilder => $subscription->trialDays(trialDays: $plan->trialDays),
            )
            ->when(
                value: $plan->allowPromotionCodes,
                callback: static fn (SubscriptionBuilder $subscription): SubscriptionBuilder => $subscription->allowPromotionCodes(),
            )
            ->when(
                value: $plan->collectTaxIds,
                callback: static fn (SubscriptionBuilder $subscription): SubscriptionBuilder => $subscription->collectTaxIds(),
            )
            ->withMetadata([
                'model' => Relation::getMorphAlias(Company::class),
            ])
            ->checkout(sessionOptions: [
                'success_url' => Dashboard::getUrl(),
                'cancel_url' => Dashboard::getUrl(),
                'customer_update' => [
                    'address' => 'auto',
                ],
            ]);

        redirect($sessionCheckout->asStripeCheckoutSession()->url);
    }
}
