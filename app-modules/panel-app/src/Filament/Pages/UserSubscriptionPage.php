<?php

namespace TresPontosTech\App\Filament\Pages;

use App\Models\Users\User;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Entities\PriceEntity;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;

class UserSubscriptionPage extends Page
{
    protected static ?string $slug = 'available-subscriptions';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::ScreenLarge;

    protected string $view = 'user-available-subscriptions';

    protected static bool $shouldRegisterNavigation = false;

    public string $selectedPlanSlug = '';

    public function mount(): void
    {
        $plans = resolve(PlanRepository::class)->getCheckoutPlansFor('user');
        $this->selectedPlanSlug = $plans->first()->slug ?? '';
    }

    protected function getViewData(): array
    {
        /** @var Company|null $tenant */
        $tenant = filament()->getTenant();
        $isFlamma = $tenant?->slug === 'flamma-company';
        $plans = resolve(PlanRepository::class)->getCheckoutPlansFor('user');

        if ($isFlamma) {
            $plans = $plans->filter(
                fn ($plan) => $plan->prices->contains(
                    fn ($p): bool => ($p->metadata['tenant'] ?? null) === 'flamma-company'
                )
            );
        }

        return [
            'plans' => $plans,
            'isFlamma' => $isFlamma,
        ];
    }

    public function checkout(string $planSlug): void
    {
        $user = auth()->user();

        $plan = resolve(PlanRepository::class)->get($planSlug);
        $this->selectedPlanSlug = $planSlug;

        $price = $this->resolvePriceForTenant($plan->prices->all());

        $data = new CheckoutData(
            planSlug: $plan->slug,
            priceId: $price->priceId,
            isMetered: false,
            quantity: 1,
            trialDays: $plan->hasGenericTrial && $plan->trialDays !== false
                ? $plan->trialDays
                : null,
            allowPromotionCodes: $plan->allowPromotionCodes,
            collectTaxIds: $plan->collectTaxIds,
            successUrl: UserDashboard::getUrl(),
            cancelUrl: UserDashboard::getUrl(),
            metadata: ['model' => Relation::getMorphAlias(User::class)],
        );

        $driver = resolve(BillingManager::class)->getDriver($plan->provider);
        $url = $driver->createCheckout(billable: $user, data: $data);

        if ($driver->checkoutOpensInNewTab()) {
            $this->dispatch('open-modal', id: 'waiting-for-payment');
            $this->js("window.open('" . addslashes($url) . "', '_blank')");

            return;
        }

        $this->redirect($url);
    }

    public function checkPaymentStatus(): void
    {
        if (blank($this->selectedPlanSlug)) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        $plan = resolve(PlanRepository::class)->get($this->selectedPlanSlug);

        $active = resolve(BillingManager::class)
            ->getDriver($plan->provider)
            ->hasActiveSubscription($user);

        if ($active) {
            $this->dispatch('close-modal', id: 'waiting-for-payment');
            $this->redirect(UserDashboard::getUrl());
        }
    }

    public function cancelWaiting(): void
    {
        $this->dispatch('close-modal', id: 'waiting-for-payment');
    }

    /** @param PriceEntity[] $prices */
    private function resolvePriceForTenant(array $prices): PriceEntity
    {
        /** @var Company|null $tenant */
        $tenant = filament()->getTenant();
        $isFlamma = $tenant?->slug === 'flamma-company';
        $prices = collect($prices);

        if ($isFlamma) {
            return $prices->first(
                fn (PriceEntity $p): bool => ($p->metadata['tenant'] ?? null) === 'flamma-company'
            ) ?? $prices->firstOrFail();
        }

        return $prices->first(
            fn (PriceEntity $p): bool => ! isset($p->metadata['tenant'])
        ) ?? $prices->firstOrFail();
    }
}
