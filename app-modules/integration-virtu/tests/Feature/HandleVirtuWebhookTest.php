<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\IntegrationVirtu\Actions\HandleVirtuWebhook;
use TresPontosTech\IntegrationVirtu\DTO\VirtuWebhookDTO;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

function virtuWebhookDto(array $data = [], string $event = 'TRANSACTION'): VirtuWebhookDTO
{
    return VirtuWebhookDTO::fromArray([
        'event' => $event,
        'idempotencyKey' => 'transaction:SALE:1003:SUCCESS',
        'occurredAt' => '2026-08-03T10:00:00.000Z',
        'data' => array_merge([
            'saleId' => 1003,
            'checkoutId' => 'checkout_fake1',
            'status' => 'SUCCESS',
            'paymentStatus' => 'PAID',
            'customer' => ['cpf' => '111.222.333-44', 'name' => 'Carlos', 'email' => 'carlos@example.com'],
            'subscriptions' => [['id' => 'sub_1']],
        ], $data),
    ]);
}

function pendingVirtuSubscription(User $user, array $overrides = []): Subscription
{
    return Subscription::query()->create(array_merge([
        'subscriptionable_type' => $user->getMorphClass(),
        'subscriptionable_id' => $user->getKey(),
        'type' => 'virtu-gold',
        // createCheckout keys the row on the checkout reference the webhook reports.
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'pending',
        'stripe_price' => 'pp_virtu_gold',
        'quantity' => 1,
    ], $overrides));
}

it('activates the pending subscription written at checkout', function (): void {
    Event::fake([SubscriptionActivated::class]);

    pendingVirtuSubscription($this->user);

    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto());

    Event::assertDispatched(SubscriptionActivated::class, function (SubscriptionActivated $event): bool {
        return $event->dto->subscriptionExternalId === 'checkout_fake1'
            && $event->dto->billableId === $this->user->getKey()
            && $event->dto->planExternalId === 'pp_virtu_gold'
            && $event->dto->planSlug === 'virtu-gold'
            && $event->dto->status === 'active';
    });
});

it('flips the same row instead of inserting a second one', function (): void {
    pendingVirtuSubscription($this->user);

    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto());

    // No Virtu-specific persistence: the module emits the agnostic event and
    // billing's own listener runs UpsertSubscription, which keys on stripe_id.
    assertDatabaseCount('billing_subscriptions', 1);

    assertDatabaseHas('billing_subscriptions', [
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'active',
        'stripe_price' => 'pp_virtu_gold',
        'subscriptionable_id' => $this->user->getKey(),
    ]);
});

it('keeps landing recurring charges on the same subscription', function (): void {
    pendingVirtuSubscription($this->user);

    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto());
    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto(['saleId' => 2004]));

    assertDatabaseCount('billing_subscriptions', 1);
});

it('ignores a charge it cannot attribute instead of failing', function (): void {
    Event::fake([SubscriptionActivated::class]);

    pendingVirtuSubscription($this->user);

    // Nothing matches: with no metadata and no customer id, an unknown checkout
    // reference is unattributable and retrying will not change that.
    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto(['checkoutId' => 'checkout_unknown']));

    Event::assertNotDispatched(SubscriptionActivated::class);
});

it('ignores a declined transaction', function (): void {
    Event::fake([SubscriptionActivated::class]);

    pendingVirtuSubscription($this->user);

    resolve(HandleVirtuWebhook::class)->handle(
        virtuWebhookDto(['status' => 'DECLINED', 'paymentStatus' => 'DECLINED'])
    );

    Event::assertNotDispatched(SubscriptionActivated::class);
});

it('ignores events with no mapping yet', function (): void {
    Event::fake([SubscriptionActivated::class]);

    pendingVirtuSubscription($this->user);

    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto(event: 'WITHDRAWAL'));

    Event::assertNotDispatched(SubscriptionActivated::class);
});

it('credits a paid order matched by checkout id', function (): void {
    Event::fake([OrderCreditPurchased::class, SubscriptionActivated::class]);

    $order = CreditOrder::factory()->create([
        'provider' => BillingProviderEnum::Virtu,
        'checkout_id' => 'checkout_fake1',
    ]);

    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto());

    Event::assertDispatched(
        OrderCreditPurchased::class,
        fn (OrderCreditPurchased $event): bool => $event->creditOrderId === $order->getKey()
    );
    Event::assertNotDispatched(SubscriptionActivated::class);
});

it('does not credit an order belonging to another provider', function (): void {
    Event::fake([OrderCreditPurchased::class]);

    CreditOrder::factory()->create([
        'provider' => BillingProviderEnum::Barte,
        'checkout_id' => 'checkout_fake1',
    ]);

    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto());

    Event::assertNotDispatched(OrderCreditPurchased::class);
});
