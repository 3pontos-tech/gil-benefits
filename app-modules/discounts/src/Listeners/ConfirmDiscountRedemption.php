<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Listeners;

use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;

/**
 * A reserva só vira ativa no pagamento confirmado, não no clique do checkout, porque um link
 * abandonado não pode consumir o cupom nem prender a pessoa ao plano errado. Uma reserva
 * vencida ou liberada que for paga continua valendo, pois o desconto já foi cobrado.
 */
final class ConfirmDiscountRedemption
{
    public function handle(SubscriptionActivated $event): void
    {
        $redemption = DiscountRedemption::query()
            ->with(['discount', 'grant'])
            ->where('subscription_external_id', $event->dto->subscriptionExternalId)
            ->whereIn('status', [DiscountRedemptionStatusEnum::Reserved, DiscountRedemptionStatusEnum::Released])
            ->first();

        if ($redemption === null) {
            return;
        }

        $redemption->update([
            'status' => DiscountRedemptionStatusEnum::Active,
            'confirmed_at' => now(),
        ]);

        $this->warnWhenOverTotalLimit($redemption->discount);
        $this->lockPlan($redemption, $event->dto->planSlug);
    }

    private function warnWhenOverTotalLimit(Discount $discount): void
    {
        if ($discount->max_redemptions === null) {
            return;
        }

        $confirmedCount = DiscountRedemption::query()
            ->where('discount_id', $discount->getKey())
            ->whereIn('status', [DiscountRedemptionStatusEnum::Active, DiscountRedemptionStatusEnum::Completed])
            ->count();

        if ($confirmedCount > $discount->max_redemptions) {
            Log::warning('Cupom pago acima do limite total', [
                'discount_id' => $discount->getKey(),
                'max_redemptions' => $discount->max_redemptions,
                'confirmed_count' => $confirmedCount,
            ]);
        }
    }

    private function lockPlan(DiscountRedemption $redemption, string $paidPlanSlug): void
    {
        $grant = $redemption->grant;

        if (! $redemption->discount->locks_plan || ! $grant instanceof DiscountGrant) {
            return;
        }

        if ($grant->plan_slug === null) {
            $grant->update([
                'plan_slug' => $paidPlanSlug,
                'first_redeemed_at' => now(),
            ]);

            return;
        }

        if ($grant->plan_slug !== $paidPlanSlug) {
            Log::warning('Assinatura com desconto em plano diferente do travado', [
                'redemption_id' => $redemption->getKey(),
                'grant_id' => $grant->getKey(),
                'locked_plan' => $grant->plan_slug,
                'paid_plan' => $paidPlanSlug,
            ]);
        }
    }
}
