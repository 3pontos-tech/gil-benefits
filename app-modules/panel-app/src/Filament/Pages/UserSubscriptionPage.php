<?php

namespace TresPontosTech\PanelApp\Filament\Pages;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Entities\PriceEntity;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Discounts\Actions\ListAvailableDiscounts;
use TresPontosTech\Discounts\Actions\RedeemDiscountCode;
use TresPontosTech\Discounts\Actions\ReserveDiscountRedemption;
use TresPontosTech\Discounts\DTOs\DiscountOffer;
use TresPontosTech\Discounts\Models\DiscountRedemption;

class UserSubscriptionPage extends Page
{
    protected static ?string $slug = 'available-subscriptions';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::ScreenLarge;

    protected string $view = 'user-available-subscriptions';

    protected static bool $shouldRegisterNavigation = false;

    public string $selectedPlanSlug = '';

    /** @var array<string, string> Cupom escolhido por plano; string vazia significa sem cupom. */
    public array $selectedOffers = [];

    public string $discountCode = '';

    public function mount(): void
    {
        $this->selectedPlanSlug = $this->checkoutPlans()->first()->slug ?? '';
        $this->preselectBestOffers();
    }

    protected function getViewData(): array
    {
        $plans = $this->checkoutPlans();

        return [
            'plans' => $plans,
            'audience' => $this->audienceForTenant(),
            'offersByPlan' => $plans->mapWithKeys(fn (PlanEntity $plan): array => [
                $plan->slug => $this->offersFor($plan),
            ])->all(),
        ];
    }

    public function applyCode(): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:64']]);

        /** @var User $user */
        $user = auth()->user();

        try {
            resolve(RedeemDiscountCode::class)->handle($user, $this->discountCode);
        } catch (ValidationException $validationException) {
            $this->addError('discountCode', $validationException->validator->errors()->first('code'));

            return;
        }

        $this->discountCode = '';
        $this->preselectBestOffers();

        Notification::make()->success()->title('Cupom adicionado à sua carteira')->send();
    }

    public function checkout(string $planSlug, ?string $offerKey = null): void
    {
        /** @var User $user */
        $user = auth()->user();

        $plan = resolve(PlanRepository::class)->get($planSlug);
        $this->selectedPlanSlug = $planSlug;

        // O servidor refaz a lista e nunca confia no valor ou no percentual vindos da tela: do cliente vem só a key.
        $offer = null;

        if (filled($offerKey)) {
            $offer = $this->offersFor($plan)->first(
                fn (DiscountOffer $candidate): bool => $candidate->key === $offerKey
            );

            if (! $offer instanceof DiscountOffer) {
                $this->preselectBestOffers();
                Notification::make()->danger()->title('Este cupom não está mais disponível')->send();

                return;
            }

            $redemption = resolve(ReserveDiscountRedemption::class)->handle($user, $offer, $plan->slug);

            if (! $redemption instanceof DiscountRedemption) {
                $this->preselectBestOffers();
                Notification::make()->danger()->title('Este cupom atingiu o limite de usos')->send();

                return;
            }
        }

        $this->selectedOffers[$planSlug] = $offerKey ?? '';

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
            discount: $offer?->checkoutDiscount,
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

    /** @return Collection<int, PlanEntity> */
    private function checkoutPlans(): Collection
    {
        $audience = $this->audienceForTenant();

        // Um plano sem preço para esta audiência não tem o que cobrar aqui, e
        // mostrá-lo levaria o checkout a escolher o preço da outra audiência.
        return resolve(PlanRepository::class)
            ->getCheckoutPlansFor('user')
            ->filter(fn (PlanEntity $plan): bool => $plan->prices->contains(
                fn (PriceEntity $price): bool => $price->audience === $audience
            ));
    }

    /** @return Collection<int, DiscountOffer> */
    private function offersFor(PlanEntity $plan): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return resolve(ListAvailableDiscounts::class)->handle(
            $user,
            $plan,
            $this->audienceForTenant(),
            resolve(BillingManager::class)->getDriver($plan->provider),
        );
    }

    private function preselectBestOffers(): void
    {
        $this->selectedOffers = $this->checkoutPlans()
            ->mapWithKeys(fn (PlanEntity $plan): array => [
                $plan->slug => $this->offersFor($plan)->pluck('key')->first() ?? '',
            ])
            ->all();
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
