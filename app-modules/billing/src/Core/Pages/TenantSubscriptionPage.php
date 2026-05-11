<?php

namespace TresPontosTech\Billing\Core\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Computed;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
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

    public string $driver = 'barte';

    protected function getViewData(): array
    {
        return [
            'plan' => $this->getActiveTenantPlan(),
        ];
    }

    #[Computed]
    public function getActiveTenantPlan(): PlanEntity
    {
        return resolve(PlanRepository::class)->getActiveTenantPlan(BillingProviderEnum::from($this->driver));
    }

    public function checkout(): void
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $plan = $this->getActiveTenantPlan();

        $price = $plan->prices->first();

        $seats = $this->seatsAmount;

        if ($seats < 5) {
            $seats = 5;
        }

        $data = new CheckoutData(
            planSlug: $plan->slug,
            priceId: $price->priceId,
            isMetered: $plan->isMeteredPrice,
            quantity: max($this->seatsAmount, 5),
            trialDays: $plan->hasGenericTrial && $plan->trialDays !== false
                ? $plan->trialDays
                : null,
            allowPromotionCodes: $plan->allowPromotionCodes,
            collectTaxIds: $plan->collectTaxIds,
            successUrl: Dashboard::getUrl(),
            cancelUrl: Dashboard::getUrl(),
            metadata: ['model' => Relation::getMorphAlias(Company::class)],
        );

        $driver = resolve(BillingManager::class)->getDriver(BillingProviderEnum::from($this->driver));
        $url = $driver->createCheckout($tenant, $data);

        if ($driver->checkoutOpensInNewTab()) {
            $this->dispatch('open-modal', id: 'waiting-for-payment');
            $this->js("window.open('" . addslashes($url) . "', '_blank')");

            return;
        }

        $this->redirect($url);
    }

    public function checkPaymentStatus(): void
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $active = resolve(BillingManager::class)
            ->getDriver(BillingProviderEnum::from($this->driver))
            ->hasActiveSubscription($tenant);

        if ($active) {
            $this->dispatch('close-modal', id: 'waiting-for-payment');
            $this->redirect(Dashboard::getUrl());
        }
    }

    public function cancelWaiting(): void
    {
        $this->dispatch('close-modal', id: 'waiting-for-payment');
    }
}
