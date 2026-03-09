<?php

namespace TresPontosTech\App\Filament\Pages;

use App\Models\Users\User;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\SubscriptionBuilder;
use Livewire\Attributes\Computed;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;

class UserSubscriptionPage extends Page
{
    protected static ?string $slug = 'available-subscriptions';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::ScreenLarge;

    protected string $view = 'user-available-subscriptions';

    protected static bool $shouldRegisterNavigation = false;

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
        return resolve(PlanRepository::class)->getPlansFor($type);
    }

    public function checkout(): void
    {
        $user = auth()->user();
        Cashier::useCustomerModel(User::class);

        $plan = resolve(PlanRepository::class)->get($this->selectedPlan);
        $price = $plan->prices->first();

        $sessionCheckout = $user
            ->newSubscription(type: $plan->slug, prices: [$price->priceId])
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
            ->withMetadata([
                'model' => Relation::getMorphAlias(User::class),
            ])
            ->checkout(sessionOptions: [
                'success_url' => UserDashboard::getUrl(),
                'cancel_url' => UserDashboard::getUrl(),
            ]);

        redirect($sessionCheckout->asStripeCheckoutSession()->url);
    }
}
