<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\IntegrationVirtu\Jobs\HandleVirtuWebhookJob;

beforeEach(function (): void {
    $user = User::factory()->create();

    Subscription::query()->create([
        'subscriptionable_type' => $user->getMorphClass(),
        'subscriptionable_id' => $user->getKey(),
        'type' => 'virtu-gold',
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'pending',
        'stripe_price' => 'pp_virtu_gold',
        'quantity' => 1,
    ]);
});

function virtuJobPayload(?string $idempotencyKey = 'transaction:SALE:1003:SUCCESS'): array
{
    return array_filter([
        'event' => 'TRANSACTION',
        'idempotencyKey' => $idempotencyKey,
        'data' => [
            'saleId' => 1003,
            'checkoutId' => 'checkout_fake1',
            'status' => 'SUCCESS',
            'paymentStatus' => 'PAID',
            'subscriptions' => [['id' => 'sub_1']],
        ],
    ], fn (mixed $value): bool => $value !== null);
}

it('processes a webhook once and drops the redelivery', function (): void {
    Event::fake([SubscriptionActivated::class]);

    dispatch_sync(new HandleVirtuWebhookJob(virtuJobPayload()));
    dispatch_sync(new HandleVirtuWebhookJob(virtuJobPayload()));

    // Virtu sends the same idempotency key on a redelivery, which Barte never
    // offered — so a repeat is dropped instead of reprocessing.
    Event::assertDispatchedTimes(SubscriptionActivated::class, 1);
});

it('processes a payload with no idempotency key rather than discarding it', function (): void {
    Event::fake([SubscriptionActivated::class]);

    dispatch_sync(new HandleVirtuWebhookJob(virtuJobPayload(null)));

    // Better a duplicate than a silently dropped charge.
    Event::assertDispatchedTimes(SubscriptionActivated::class, 1);
});
