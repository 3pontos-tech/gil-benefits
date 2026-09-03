<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCancelled;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionDefaulted;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Credits\Events\OrderCreditPurchased;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\IntegrationVirtu\Actions\HandleVirtuWebhook;
use TresPontosTech\IntegrationVirtu\DTO\VirtuWebhookDTO;
use TresPontosTech\IntegrationVirtu\VirtuAdapter;

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

/**
 * Shape observed in the sandbox when a subscription is cancelled from the Virtu
 * panel: event TRANSACTION, `paymentStatus` still PAID, and only `data.source`
 * telling it apart from a sale.
 */
function virtuCancellationDto(array $data = []): VirtuWebhookDTO
{
    return virtuWebhookDto(array_merge([
        'status' => 'CANCELED',
        'paymentStatus' => 'PAID',
        'source' => 'SUBSCRIPTION_STATUS_CHANGED',
        'saleSubscriptionStatus' => 'CANCELED',
        'previousStatus' => 'ACTIVE',
        'subscriptionId' => 58,
        'subscriptions' => [['id' => '58', 'subscriptionId' => 58, 'status' => 'CANCELED']],
    ], $data));
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

it('deactivates the subscription when the panel cancels it', function (): void {
    Event::fake([SubscriptionCancelled::class, SubscriptionActivated::class]);

    pendingVirtuSubscription($this->user, ['stripe_status' => 'active']);

    resolve(HandleVirtuWebhook::class)->handle(virtuCancellationDto());

    Event::assertNotDispatched(SubscriptionActivated::class);
    Event::assertDispatched(SubscriptionCancelled::class, function (SubscriptionCancelled $event): bool {
        return $event->dto->subscriptionExternalId === 'checkout_fake1'
            && $event->dto->billableId === $this->user->getKey()
            && $event->dto->status === 'inactive'
            && $event->dto->endsAt instanceof Carbon;
    });
});

it('leaves a cancelled subscription without access', function (): void {
    pendingVirtuSubscription($this->user, ['stripe_status' => 'active']);

    resolve(HandleVirtuWebhook::class)->handle(virtuCancellationDto());

    assertDatabaseCount('billing_subscriptions', 1);
    assertDatabaseHas('billing_subscriptions', [
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'inactive',
    ]);
});

it('never reads a cancellation as an approved charge', function (): void {
    // paymentStatus stays PAID on a cancellation, so isPaid() must weigh the
    // terminal sale status above it or cancelling would re-activate the row.
    expect(virtuCancellationDto()->isPaid())->toBeFalse();
});

it('ignores a subscription status it has no mapping for', function (): void {
    Event::fake([SubscriptionCancelled::class, SubscriptionActivated::class]);

    pendingVirtuSubscription($this->user, ['stripe_status' => 'active']);

    resolve(HandleVirtuWebhook::class)->handle(virtuCancellationDto([
        'saleSubscriptionStatus' => 'PAUSED',
        'subscriptions' => [['id' => '58', 'status' => 'PAUSED']],
    ]));

    Event::assertNotDispatched(SubscriptionCancelled::class);
    Event::assertNotDispatched(SubscriptionActivated::class);
    assertDatabaseHas('billing_subscriptions', [
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'active',
    ]);
});

it('ignores a cancellation it cannot attribute', function (): void {
    Event::fake([SubscriptionCancelled::class]);

    pendingVirtuSubscription($this->user, ['stripe_status' => 'active']);

    resolve(HandleVirtuWebhook::class)->handle(virtuCancellationDto(['checkoutId' => 'checkout_unknown']));

    Event::assertNotDispatched(SubscriptionCancelled::class);
});

it('still activates an ordinary paid sale', function (): void {
    Event::fake([SubscriptionActivated::class, SubscriptionCancelled::class]);

    pendingVirtuSubscription($this->user);

    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto());

    Event::assertDispatched(SubscriptionActivated::class);
    Event::assertNotDispatched(SubscriptionCancelled::class);
});

/**
 * Shape observed in the sandbox for a PIX that was generated and never paid:
 * status SUCCESS — the event was processed — with paymentStatus SENT.
 */
function virtuIssuedPixDto(): VirtuWebhookDTO
{
    return virtuWebhookDto(['status' => 'SUCCESS', 'paymentStatus' => 'SENT']);
}

it('never reads an issued PIX as paid', function (): void {
    expect(virtuIssuedPixDto()->isPaid())->toBeFalse();
});

it('does not settle a credit order whose PIX was only issued', function (): void {
    Event::fake([OrderCreditPurchased::class]);

    CreditOrder::factory()->create([
        'provider' => BillingProviderEnum::Virtu,
        'checkout_id' => 'checkout_fake1',
    ]);

    resolve(HandleVirtuWebhook::class)->handle(virtuIssuedPixDto());

    Event::assertNotDispatched(OrderCreditPurchased::class);
});

it('does not activate a subscription whose PIX was only issued', function (): void {
    Event::fake([SubscriptionActivated::class]);

    pendingVirtuSubscription($this->user);

    resolve(HandleVirtuWebhook::class)->handle(virtuIssuedPixDto());

    Event::assertNotDispatched(SubscriptionActivated::class);
});

/**
 * Shape observed in the sandbox when a renewal fails: nine paid cycles, then this
 * event and no SUBSCRIPTION_CHARGE for the tenth. paymentStatus stays PAID from
 * the original sale, and the sale reports PAST_DUE.
 */
function virtuDelinquencyDto(array $data = []): VirtuWebhookDTO
{
    return virtuWebhookDto(array_merge([
        'status' => 'PAST_DUE',
        'paymentStatus' => 'PAID',
        'source' => 'SUBSCRIPTION_STATUS_CHANGED',
        'saleSubscriptionStatus' => 'PENDING',
        'previousStatus' => 'ACTIVE',
        'subscriptionId' => 60,
        'subscriptions' => [['id' => '60', 'subscriptionId' => 60, 'status' => 'PENDING']],
    ], $data));
}

it('marks the subscription as defaulter when a renewal stops being paid', function (): void {
    Event::fake([SubscriptionDefaulted::class, SubscriptionActivated::class, SubscriptionCancelled::class]);

    pendingVirtuSubscription($this->user, ['stripe_status' => 'active']);

    resolve(HandleVirtuWebhook::class)->handle(virtuDelinquencyDto());

    Event::assertNotDispatched(SubscriptionActivated::class);
    Event::assertNotDispatched(SubscriptionCancelled::class);
    Event::assertDispatched(SubscriptionDefaulted::class, function (SubscriptionDefaulted $event): bool {
        return $event->dto->subscriptionExternalId === 'checkout_fake1'
            && $event->dto->billableId === $this->user->getKey()
            && $event->dto->status === 'defaulter'
            // Recoverable, so no end date: the next paid cycle activates it again.
            && ! $event->dto->endsAt instanceof Carbon;
    });
});

it('takes access away from a delinquent subscription', function (): void {
    pendingVirtuSubscription($this->user, ['stripe_status' => 'active']);

    resolve(HandleVirtuWebhook::class)->handle(virtuDelinquencyDto());

    assertDatabaseCount('billing_subscriptions', 1);
    assertDatabaseHas('billing_subscriptions', [
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'defaulter',
    ]);

    // The adapter only ever counts 'active', so the benefit is gone.
    expect(resolve(VirtuAdapter::class)->hasActiveSubscription($this->user))->toBeFalse();
});

it('reactivates the same row when the customer pays again', function (): void {
    pendingVirtuSubscription($this->user, ['stripe_status' => 'active']);

    resolve(HandleVirtuWebhook::class)->handle(virtuDelinquencyDto());
    resolve(HandleVirtuWebhook::class)->handle(virtuWebhookDto());

    assertDatabaseCount('billing_subscriptions', 1);
    assertDatabaseHas('billing_subscriptions', [
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'active',
    ]);
});

it('never reads a delinquency payload as an approved charge', function (): void {
    // paymentStatus stays PAID on this event, so routing by `source` before the
    // payment guard is what keeps it from reading as a renewal.
    expect(virtuDelinquencyDto()->isSubscriptionStatusChange())->toBeTrue();
    expect(virtuDelinquencyDto()->isDelinquent())->toBeTrue();
    expect(virtuDelinquencyDto()->isCancellation())->toBeFalse();
});

it('does not read a chargeback or a dispute as paid', function (string $paymentStatus): void {
    Event::fake([SubscriptionActivated::class]);

    pendingVirtuSubscription($this->user);

    // status stays SUCCESS on these, so paymentStatus is the only guard.
    resolve(HandleVirtuWebhook::class)->handle(
        virtuWebhookDto(['status' => 'SUCCESS', 'paymentStatus' => $paymentStatus])
    );

    Event::assertNotDispatched(SubscriptionActivated::class);
})->with(['CHARGEBACK', 'DISPUTE', 'DISPUTE_ALERT', 'PARTIALLY_PAID', 'DEFAULTER']);

/**
 * Shape confirmed in writing by Pagaa: a recovered payment arrives as a status
 * change PAST_DUE → ACTIVE, never as a SUBSCRIPTION_CHARGE.
 */
function virtuRecoveryDto(array $data = []): VirtuWebhookDTO
{
    return virtuWebhookDto(array_merge([
        'status' => 'ACTIVE',
        'paymentStatus' => 'PAID',
        'source' => 'SUBSCRIPTION_STATUS_CHANGED',
        'saleSubscriptionStatus' => 'ACTIVE',
        'previousStatus' => 'PAST_DUE',
        'subscriptionId' => 66,
        'subscriptions' => [['id' => '66', 'subscriptionId' => 66, 'status' => 'ACTIVE']],
    ], $data));
}

it('gives access back when a delinquent subscription is paid again', function (): void {
    Event::fake([SubscriptionActivated::class, SubscriptionCancelled::class, SubscriptionDefaulted::class]);

    pendingVirtuSubscription($this->user, ['stripe_status' => 'defaulter']);

    resolve(HandleVirtuWebhook::class)->handle(virtuRecoveryDto());

    Event::assertNotDispatched(SubscriptionCancelled::class);
    Event::assertNotDispatched(SubscriptionDefaulted::class);
    Event::assertDispatched(SubscriptionActivated::class, function (SubscriptionActivated $event): bool {
        return $event->dto->subscriptionExternalId === 'checkout_fake1'
            && $event->dto->billableId === $this->user->getKey()
            && $event->dto->status === 'active'
            && ! $event->dto->endsAt instanceof Carbon;
    });
});

it('restores the benefit of a subscription that recovered', function (): void {
    pendingVirtuSubscription($this->user, ['stripe_status' => 'defaulter']);

    expect(resolve(VirtuAdapter::class)->hasActiveSubscription($this->user))->toBeFalse();

    resolve(HandleVirtuWebhook::class)->handle(virtuRecoveryDto());

    assertDatabaseCount('billing_subscriptions', 1);
    assertDatabaseHas('billing_subscriptions', [
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'active',
    ]);

    expect(resolve(VirtuAdapter::class)->hasActiveSubscription($this->user))->toBeTrue();
});

/**
 * Shape confirmed in writing by Pagaa: the expiry routine reports EXPIRED, which
 * maps to saleSubscriptionStatus INACTIVE, up to 24h after the cancellation.
 */
function virtuExpiryDto(array $data = []): VirtuWebhookDTO
{
    return virtuWebhookDto(array_merge([
        'status' => 'EXPIRED',
        'paymentStatus' => 'PAID',
        'source' => 'SUBSCRIPTION_STATUS_CHANGED',
        'saleSubscriptionStatus' => 'INACTIVE',
        'previousStatus' => 'CANCELED',
        'subscriptionId' => 66,
        'subscriptions' => [['id' => '66', 'subscriptionId' => 66, 'status' => 'INACTIVE']],
    ], $data));
}

it('keeps access off when the expiry routine closes a cancelled subscription', function (): void {
    pendingVirtuSubscription($this->user, ['stripe_status' => 'inactive']);

    resolve(HandleVirtuWebhook::class)->handle(virtuExpiryDto());

    assertDatabaseHas('billing_subscriptions', [
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'inactive',
    ]);

    expect(resolve(VirtuAdapter::class)->hasActiveSubscription($this->user))->toBeFalse();
});

it('reads each subscription lifecycle status as its own transition', function (): void {
    expect(virtuRecoveryDto()->isSubscriptionStatusChange())->toBeTrue()
        ->and(virtuRecoveryDto()->isCancellation())->toBeFalse()
        ->and(virtuRecoveryDto()->isDelinquent())->toBeFalse()
        ->and(virtuExpiryDto()->isCancellation())->toBeTrue()
        ->and(virtuExpiryDto()->isDelinquent())->toBeFalse();
});
