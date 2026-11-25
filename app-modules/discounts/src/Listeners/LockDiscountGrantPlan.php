<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Listeners;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Discounts\Models\DiscountGrant;

/**
 * O plano é travado no pagamento confirmado, não no clique do checkout, porque um link
 * abandonado não pode prender a pessoa ao plano errado.
 */
final class LockDiscountGrantPlan
{
    public function handle(SubscriptionActivated $event): void
    {
        $subscription = Subscription::query()
            ->where('stripe_id', $event->dto->subscriptionExternalId)
            ->first();

        if ($subscription === null || $subscription->discount_percent === null) {
            return;
        }

        $billableType = Relation::getMorphedModel($subscription->subscriptionable_type) ?? $subscription->subscriptionable_type;

        if ($billableType !== User::class) {
            return;
        }

        DiscountGrant::query()
            ->where('user_id', $subscription->subscriptionable_id)
            ->whereNotNull('claimed_at')
            ->whereNull('plan_slug')
            ->update([
                'plan_slug' => $event->dto->planSlug,
                'first_redeemed_at' => now(),
            ]);
    }
}
