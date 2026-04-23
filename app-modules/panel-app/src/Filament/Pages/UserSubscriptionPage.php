<?php

namespace TresPontosTech\App\Filament\Pages;

use App\Models\Users\User;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Laravel\Cashier\Cashier;
use Livewire\Attributes\Computed;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;

class UserSubscriptionPage extends Page
{
    protected static ?string $slug = 'available-subscriptions';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::ScreenLarge;

    protected string $view = 'user-available-subscriptions';

    protected static bool $shouldRegisterNavigation = false;

    public string $selectedPlan = 'user';

    public string $selectedProvider = 'stripe';

    protected function getViewData(): array
    {
        return [
            'plans' => $this->planRepository($this->selectedPlan),
        ];
    }

    #[Computed]
    public function planRepository(string $type): Collection
    {
        return resolve(PlanRepository::class)->getPlansFor($type);
    }

    public function checkout(): void
    {
        $user = auth()->user();
        Cashier::useCustomerModel(User::class);

        $plan = resolve(PlanRepository::class)->get($this->selectedPlan);
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

        $url = resolve(BillingManager::class)
            ->getDriver(BillingProviderEnum::from($this->selectedProvider))
            ->createCheckout(
                billable: $user,
                data: $data
            );

        redirect($url);
    }
}
