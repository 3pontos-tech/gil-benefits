<?php

namespace TresPontosTech\App\Filament\Pages;

use App\Models\Users\User;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;

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
        $plans = resolve(PlanRepository::class)->getPlansFor('user');
        $this->selectedPlanSlug = $plans->first()?->slug ?? '';
    }

    protected function getViewData(): array
    {
        return ['plans' => resolve(PlanRepository::class)->getPlansFor('user')];
    }

    public function checkout(string $planSlug): void
    {
        $user = auth()->user();

        $plan = resolve(PlanRepository::class)->get($planSlug);
        $this->selectedPlanSlug = $planSlug;
        $price = $plan->prices->first();
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
}
