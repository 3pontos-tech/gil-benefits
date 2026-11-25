<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Actions;

use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsCheckoutDiscount;
use TresPontosTech\Billing\Core\Contracts\SupportsLimitedDurationDiscount;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Entities\PriceEntity;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Discounts\DTOs\DiscountCandidate;
use TresPontosTech\Discounts\DTOs\DiscountOffer;
use TresPontosTech\Discounts\Enums\DiscountCustomerTypeEnum;
use TresPontosTech\Discounts\Enums\DiscountDistributionEnum;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;

final readonly class ListAvailableDiscounts
{
    private const COMPARISON_CYCLES = 12;

    /**
     * Cupons que a pessoa pode usar neste plano, do mais vantajoso ao menos vantajoso.
     * O primeiro item é o pré-selecionado e o único que o checkout aplica.
     *
     * @return Collection<int, DiscountOffer>
     */
    public function handle(User $user, PlanEntity $plan, PriceAudienceEnum $audience, BillingContract $gateway): Collection
    {
        $price = $plan->prices->first(fn (PriceEntity $price): bool => $price->audience === $audience);

        if (! $price instanceof PriceEntity || ! $gateway instanceof SupportsCheckoutDiscount) {
            return collect();
        }

        $supportsLimitedDuration = $gateway instanceof SupportsLimitedDurationDiscount;

        return $this->candidates($user)
            ->filter(fn (DiscountCandidate $candidate): bool => $this->isEligible($candidate, $user, $plan, $audience, $supportsLimitedDuration))
            ->map(fn (DiscountCandidate $candidate): DiscountOffer => $this->offer($candidate, $price))
            ->sortByDesc(fn (DiscountOffer $offer): int => $offer->savingPerCycleCents)
            ->sortBy(fn (DiscountOffer $offer): int => $offer->expiresAt?->getTimestamp() ?? PHP_INT_MAX)
            ->sortByDesc(fn (DiscountOffer $offer): int => $offer->score)
            ->values();
    }

    /**
     * @return Collection<int, DiscountCandidate>
     */
    private function candidates(User $user): Collection
    {
        $grants = DiscountGrant::query()
            ->with('discount')
            ->where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->get()
            ->map(fn (DiscountGrant $grant): DiscountCandidate => new DiscountCandidate($grant->discount, $grant));

        $automatic = Discount::query()
            ->where('distribution', DiscountDistributionEnum::Automatic)
            ->get()
            ->map(fn (Discount $discount): DiscountCandidate => new DiscountCandidate($discount));

        return $grants->toBase()->merge($automatic);
    }

    private function isEligible(DiscountCandidate $candidate, User $user, PlanEntity $plan, PriceAudienceEnum $audience, bool $supportsLimitedDuration): bool
    {
        $discount = $candidate->discount;

        return $discount->active
            && $this->isWithinWindow($discount)
            && ($discount->audience === null || $discount->audience === $audience)
            && ($discount->eligible_plan_slugs === null || in_array($plan->slug, $discount->eligible_plan_slugs, true))
            && $discount->customer_type === DiscountCustomerTypeEnum::Person
            && ($supportsLimitedDuration || $discount->duration === DiscountDurationEnum::Forever)
            && ($candidate->grant?->plan_slug === null || $candidate->grant->plan_slug === $plan->slug)
            && (! $discount->first_subscription_only || ! $this->hasPaidSubscription($user))
            && $this->hasRedemptionCapacity($discount, $user);
    }

    private function isWithinWindow(Discount $discount): bool
    {
        return ($discount->starts_at === null || $discount->starts_at->lte(now()))
            && ($discount->ends_at === null || $discount->ends_at->gt(now()));
    }

    private function hasPaidSubscription(User $user): bool
    {
        return Subscription::query()
            ->where('subscriptionable_type', $user->getMorphClass())
            ->where('subscriptionable_id', $user->getKey())
            ->where('stripe_status', '!=', 'pending')
            ->exists();
    }

    private function hasRedemptionCapacity(Discount $discount, User $user): bool
    {
        $redemptions = DiscountRedemption::query()
            ->where('discount_id', $discount->getKey())
            ->counting();

        $isTotalFull = $discount->max_redemptions !== null
            && (clone $redemptions)->count() >= $discount->max_redemptions;

        $isPersonFull = $discount->max_redemptions_per_customer !== null
            && (clone $redemptions)
                ->where('billable_type', $user->getMorphClass())
                ->where('billable_id', $user->getKey())
                ->count() >= $discount->max_redemptions_per_customer;

        return ! $isTotalFull && ! $isPersonFull;
    }

    private function offer(DiscountCandidate $candidate, PriceEntity $price): DiscountOffer
    {
        $discount = $candidate->discount;

        $checkoutDiscount = new CheckoutDiscount(
            percentOff: $discount->percent_off,
            amountOffCents: $discount->amount_off_cents,
            durationCycles: match ($discount->duration) {
                DiscountDurationEnum::Forever => null,
                DiscountDurationEnum::Once => 1,
                DiscountDurationEnum::Repeating => $discount->duration_cycles,
            },
        );

        $discountedCents = $checkoutDiscount->apply($price->priceInCents);
        $savingPerCycleCents = $price->priceInCents - $discountedCents;
        $cycles = min(self::COMPARISON_CYCLES, $checkoutDiscount->durationCycles ?? self::COMPARISON_CYCLES);

        return new DiscountOffer(
            key: $candidate->grant instanceof DiscountGrant
                ? 'grant:' . $candidate->grant->getKey()
                : 'discount:' . $discount->getKey(),
            label: $discount->name,
            checkoutDiscount: $checkoutDiscount,
            listAmountCents: $price->priceInCents,
            discountedAmountCents: $discountedCents,
            savingPerCycleCents: $savingPerCycleCents,
            score: $savingPerCycleCents * $cycles,
            expiresAt: $this->expiresAt($candidate),
            discount: $discount,
            grant: $candidate->grant,
        );
    }

    private function expiresAt(DiscountCandidate $candidate): ?Carbon
    {
        return collect([$candidate->grant?->expires_at, $candidate->discount->ends_at])
            ->filter()
            ->sortBy(fn (Carbon $date): int => $date->getTimestamp())
            ->first();
    }
}
