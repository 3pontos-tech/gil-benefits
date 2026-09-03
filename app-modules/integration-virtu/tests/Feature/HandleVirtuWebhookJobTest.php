<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
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

it('releases the idempotency key when processing fails, so the retry lands', function (): void {
    Event::fake([SubscriptionActivated::class]);

    // Stands in for any transient database failure during $action->handle().
    Schema::rename('billing_subscriptions', 'billing_subscriptions_away');

    expect(fn (): mixed => dispatch_sync(new HandleVirtuWebhookJob(virtuJobPayload())))
        ->toThrow(QueryException::class);

    expect(Cache::has('virtu:webhook:transaction:SALE:1003:SUCCESS'))->toBeFalse();

    Schema::rename('billing_subscriptions_away', 'billing_subscriptions');

    dispatch_sync(new HandleVirtuWebhookJob(virtuJobPayload()));

    Event::assertDispatchedTimes(SubscriptionActivated::class, 1);
});

it('keeps the key after a successful run so a later redelivery is still dropped', function (): void {
    dispatch_sync(new HandleVirtuWebhookJob(virtuJobPayload()));

    expect(Cache::get('virtu:webhook:transaction:SALE:1003:SUCCESS'))->toBeTrue();
});
