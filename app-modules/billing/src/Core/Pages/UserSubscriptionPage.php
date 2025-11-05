<?php

namespace TresPontosTech\Billing\Core\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Laravel\Cashier\SubscriptionBuilder;
use Livewire\Attributes\Computed;
use TresPontosTech\Billing\Core\PlanRepository;
use TresPontosTech\Billing\Core\Price;
use TresPontosTech\Company\Models\Company;

class UserSubscriptionPage extends Page
{
    protected static ?string $slug = 'available-subscriptions';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::ScreenLarge;

    protected string $view = 'user-available-subscriptions';

    public string $selectedPlan = 'user';

    public int $seatsAmount = 5;

    protected function getViewData(): array
    {
        return [
            'plans' => $this->planRepository($this->selectedPlan),
        ];
    }

    #[Computed]
    public function planRepository(string $type): Collection
    {
        return app(PlanRepository::class)->getPlansFor($type);
    }

    public function checkout(string $plan_id)
    {

        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $plan = app(PlanRepository::class)->get($plan_id);
        /** @var Price $prices */
        $price = $plan->prices->first();

        $sessionCheckout = $tenant
            ->newSubscription(type: $plan->type, prices: [$price->priceId])
            ->when(
                value: $plan->hasGenericTrial && $plan->trialDays !== false,
                callback: static fn (SubscriptionBuilder $subscription): SubscriptionBuilder => $subscription->trialDays(trialDays: $plan->trialDays),
            )
            ->when(
                value: $plan->allowPromotionCodes === true,
                callback: static fn (SubscriptionBuilder $subscription): SubscriptionBuilder => $subscription->allowPromotionCodes(),
            )
            ->when(
                value: $plan->collectTaxIds === true,
                callback: static fn (SubscriptionBuilder $subscription): SubscriptionBuilder => $subscription->collectTaxIds(),
            )
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
