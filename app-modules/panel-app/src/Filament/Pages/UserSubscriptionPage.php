<?php

namespace TresPontosTech\PanelApp\Filament\Pages;

use App\Models\Users\User;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Entities\PriceEntity;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
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
        $audience = $this->audienceForTenant();

        // Um plano sem preço para esta audiência não tem o que cobrar aqui, e
        // mostrá-lo levaria o checkout a escolher o preço da outra audiência.
        $plans = resolve(PlanRepository::class)
            ->getCheckoutPlansFor('user')
            ->filter(fn (PlanEntity $plan): bool => $plan->prices->contains(
                fn (PriceEntity $price): bool => $price->audience === $audience
            ));

        return [
            'plans' => $plans,
            'audience' => $audience,
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
            metadata: ['model' => (string) Relation::getMorphAlias(User::class)],
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
        $audience = $this->audienceForTenant();

        // Sem fallback de propósito: cobrar o preço da outra audiência é pior
        // que falhar, porque passa despercebido.
        return collect($prices)->firstOrFail(
            fn (PriceEntity $price): bool => $price->audience === $audience
        );
    }

    private function audienceForTenant(): PriceAudienceEnum
    {
        /** @var Company|null $tenant */
        $tenant = filament()->getTenant();

        return $tenant?->subsidizesEmployees() ?? true
            ? PriceAudienceEnum::Subsidized
            : PriceAudienceEnum::Standalone;
    }
}
