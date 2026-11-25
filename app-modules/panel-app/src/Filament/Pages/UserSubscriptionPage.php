<?php

namespace TresPontosTech\PanelApp\Filament\Pages;

use App\Models\Users\User;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\SupportsCheckoutDiscount;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Entities\PriceEntity;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Discounts\Actions\ResolveApplicableDiscount;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Models\Discount;

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
            'discounts' => $plans->mapWithKeys(fn (PlanEntity $plan): array => [
                $plan->slug => $this->discountPercentFor($plan),
            ])->filter()->all(),
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
            discount: $this->checkoutDiscountFor($plan),
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

    private function discountPercentFor(PlanEntity $plan): ?int
    {
        return $this->applicableDiscountFor($plan)?->percent_off;
    }

    private function checkoutDiscountFor(PlanEntity $plan): ?CheckoutDiscount
    {
        $discount = $this->applicableDiscountFor($plan);

        if (! $discount instanceof Discount) {
            return null;
        }

        return new CheckoutDiscount(
            percentOff: $discount->percent_off,
            amountOffCents: $discount->amount_off_cents,
            durationCycles: $discount->duration === DiscountDurationEnum::Repeating ? $discount->duration_cycles : null,
        );
    }

    private function applicableDiscountFor(PlanEntity $plan): ?Discount
    {
        $driver = resolve(BillingManager::class)->getDriver($plan->provider);

        if (! $driver instanceof SupportsCheckoutDiscount) {
            return null;
        }

        return resolve(ResolveApplicableDiscount::class)->handle(
            auth()->user(),
            $plan->slug,
            $this->audienceForTenant(),
        )?->discount;
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
