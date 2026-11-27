<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Events\OrderCreditPurchased;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\IntegrationStripe\Actions\HandleStripeWebhook;
use TresPontosTech\IntegrationStripe\DTO\StripeWebhookDTO;
use TresPontosTech\IntegrationStripe\StripeApi;
use TresPontosTech\IntegrationStripe\Testing\FakeStripeApi;

beforeEach(function (): void {
    $this->freezeTime();
    $this->api = new FakeStripeApi;
    $this->app->instance(StripeApi::class, $this->api);

    $this->user = User::factory()->create();
    $this->user->subscriptions()->create([
        'type' => 'stripe-gold',
        'stripe_id' => 'checkout_ref_1',
        'stripe_status' => 'pending',
        'stripe_price' => 'price_gold',
        'quantity' => 1,
    ]);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function stripeSubscription(FakeStripeApi $api, string $status, array $attributes = []): void
{
    $api->subscriptions['sub_1'] = [
        'status' => $status,
        'metadata' => ['checkout_reference' => 'checkout_ref_1'],
        'cancel_at_period_end' => false,
        'cancel_at' => null,
        'ended_at' => null,
        'items' => ['object' => 'list', 'data' => [['current_period_end' => now()->addMonth()->getTimestamp()]]],
        ...$attributes,
    ];
}

function handleStripeEvent(string $type, string $objectId): void
{
    resolve(HandleStripeWebhook::class)->handle(new StripeWebhookDTO(
        eventId: 'evt_1',
        type: $type,
        objectId: $objectId,
        createdAt: now()->getTimestamp(),
    ));
}

it('translates the subscription status read from the API', function (string $stripeStatus, string $status, bool $grantsAccess): void {
    stripeSubscription($this->api, $stripeStatus);

    handleStripeEvent('customer.subscription.updated', 'sub_1');

    $row = Subscription::query()->sole();
    expect($row->stripe_id)->toBe('sub_1')
        ->and($row->stripe_status)->toBe($status)
        ->and($row->valid())->toBe($grantsAccess);
})->with([
    'active' => ['active', 'active', true],
    'past_due, still retried' => ['past_due', 'defaulter', true],
    'unpaid' => ['unpaid', 'defaulter', true],
    'incomplete, Pix not confirmed yet' => ['incomplete', 'pending', false],
    'incomplete_expired' => ['incomplete_expired', 'inactive', false],
    'canceled' => ['canceled', 'inactive', false],
]);

it('moves the checkout row to the subscription id when the session completes', function (): void {
    stripeSubscription($this->api, 'incomplete');
    $this->api->sessions['cs_1'] = ['mode' => 'subscription', 'subscription' => 'sub_1', 'payment_status' => 'unpaid'];

    handleStripeEvent('checkout.session.completed', 'cs_1');

    $row = Subscription::query()->sole();
    expect($row->stripe_id)->toBe('sub_1')
        ->and($row->stripe_status)->toBe('pending');
});

it('anchors the quota cycle when the subscription becomes active', function (): void {
    stripeSubscription($this->api, 'active');

    handleStripeEvent('customer.subscription.updated', 'sub_1');

    expect(Subscription::query()->sole()->quota_anchor_at?->getTimestamp())->toBe(now()->getTimestamp());
});

it('keeps access until the date the customer portal scheduled the cancellation for', function (): void {
    $cancelAt = now()->addDays(30)->startOfSecond();
    stripeSubscription($this->api, 'active', ['cancel_at' => $cancelAt->getTimestamp()]);

    handleStripeEvent('customer.subscription.updated', 'sub_1');

    $row = Subscription::query()->sole();
    expect($row->stripe_status)->toBe('active')
        ->and($row->ends_at?->getTimestamp())->toBe($cancelAt->getTimestamp())
        ->and($row->valid())->toBeTrue();
});

it('keeps access until the period end after a cancellation at period end', function (): void {
    $periodEnd = now()->addDays(12)->startOfSecond();
    stripeSubscription($this->api, 'active', [
        'cancel_at_period_end' => true,
        'items' => ['object' => 'list', 'data' => [['current_period_end' => $periodEnd->getTimestamp()]]],
    ]);

    handleStripeEvent('customer.subscription.updated', 'sub_1');

    $row = Subscription::query()->sole();
    expect($row->stripe_status)->toBe('active')
        ->and($row->ends_at?->getTimestamp())->toBe($periodEnd->getTimestamp())
        ->and($row->valid())->toBeTrue();
});

it('takes access away when Stripe deletes the subscription', function (): void {
    stripeSubscription($this->api, 'canceled', ['ended_at' => now()->getTimestamp()]);

    handleStripeEvent('customer.subscription.deleted', 'sub_1');

    $row = Subscription::query()->sole();
    expect($row->stripe_status)->toBe('inactive')
        ->and($row->valid())->toBeFalse();
});

it('ignores a subscription that did not start in our checkout', function (): void {
    $this->api->subscriptions['sub_other'] = ['status' => 'active', 'metadata' => [], 'cancel_at_period_end' => false, 'cancel_at' => null];

    handleStripeEvent('customer.subscription.updated', 'sub_other');

    expect(Subscription::query()->sole()->stripe_id)->toBe('checkout_ref_1');
});

it('credits a paid order once the payment is confirmed', function (string $type): void {
    Event::fake([OrderCreditPurchased::class]);
    $company = Company::factory()->create();
    $order = CreditOrder::factory()->create([
        'provider' => BillingProviderEnum::Stripe,
        'checkout_id' => 'cs_credit',
        'billable_type' => $company->getMorphClass(),
        'billable_id' => $company->getKey(),
        'company_id' => $company->getKey(),
    ]);
    $this->api->sessions['cs_credit'] = ['mode' => 'payment', 'payment_status' => 'paid'];

    handleStripeEvent($type, 'cs_credit');

    Event::assertDispatched(OrderCreditPurchased::class, fn (OrderCreditPurchased $event): bool => $event->creditOrderId === $order->getKey());
})->with(['checkout.session.completed', 'checkout.session.async_payment_succeeded']);

it('waits for a Pix credit purchase that is not paid yet', function (): void {
    Event::fake([OrderCreditPurchased::class]);
    $this->api->sessions['cs_credit'] = ['mode' => 'payment', 'payment_status' => 'unpaid'];

    handleStripeEvent('checkout.session.completed', 'cs_credit');

    Event::assertNotDispatched(OrderCreditPurchased::class);
});
