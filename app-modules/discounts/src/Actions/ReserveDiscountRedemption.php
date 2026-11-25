<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Actions;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Discounts\DTOs\DiscountOffer;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountRedemption;

/**
 * A reserva trava o cupom e conta os usos antes de criar o link. A reserva anterior da mesma
 * pessoa não entra na contagem e só é liberada depois que o limite passa, para que uma
 * tentativa recusada não invalide o link que a pessoa já tem.
 */
final readonly class ReserveDiscountRedemption
{
    private const RESERVATION_HOURS = 2;

    public function handle(User $user, DiscountOffer $offer, string $planSlug): ?DiscountRedemption
    {
        return DB::transaction(function () use ($user, $offer, $planSlug): ?DiscountRedemption {
            $discount = Discount::query()
                ->whereKey($offer->discount->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $otherRedemptions = DiscountRedemption::query()
                ->where('discount_id', $discount->getKey())
                ->counting()
                ->whereNot(fn (Builder $query): Builder => $this->ownOpenReservations($query, $user));

            if ($this->isTotalFull($discount, $otherRedemptions) || $this->isPersonFull($discount, $user, $otherRedemptions)) {
                return null;
            }

            $this->ownOpenReservations(
                DiscountRedemption::query()->where('discount_id', $discount->getKey()),
                $user,
            )->update([
                'status' => DiscountRedemptionStatusEnum::Released,
                'reserved_until' => null,
            ]);

            return DiscountRedemption::query()->create([
                'discount_id' => $discount->getKey(),
                'discount_grant_id' => $offer->grant?->getKey(),
                'billable_type' => $user->getMorphClass(),
                'billable_id' => $user->getKey(),
                'plan_slug' => $planSlug,
                'subscription_external_id' => null,
                'status' => DiscountRedemptionStatusEnum::Reserved,
                'value_type' => $discount->value_type,
                'percent_off' => $discount->percent_off,
                'amount_off_cents' => $discount->amount_off_cents,
                'duration' => $discount->duration,
                'duration_cycles' => $discount->duration_cycles,
                'list_amount_cents' => $offer->listAmountCents,
                'discounted_amount_cents' => $offer->discountedAmountCents,
                'reserved_until' => now()->addHours(self::RESERVATION_HOURS),
            ]);
        });
    }

    /**
     * @param  Builder<DiscountRedemption>  $redemptions
     */
    private function isTotalFull(Discount $discount, Builder $redemptions): bool
    {
        return $discount->max_redemptions !== null
            && (clone $redemptions)->count() >= $discount->max_redemptions;
    }

    /**
     * @param  Builder<DiscountRedemption>  $redemptions
     */
    private function isPersonFull(Discount $discount, User $user, Builder $redemptions): bool
    {
        return $discount->max_redemptions_per_customer !== null
            && $this->forUser((clone $redemptions), $user)->count() >= $discount->max_redemptions_per_customer;
    }

    /**
     * @param  Builder<DiscountRedemption>  $query
     * @return Builder<DiscountRedemption>
     */
    private function ownOpenReservations(Builder $query, User $user): Builder
    {
        return $this->forUser($query, $user)
            ->where('status', DiscountRedemptionStatusEnum::Reserved);
    }

    /**
     * @param  Builder<DiscountRedemption>  $query
     * @return Builder<DiscountRedemption>
     */
    private function forUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('billable_type', $user->getMorphClass())
            ->where('billable_id', $user->getKey());
    }
}
