<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\IntegrationStripe\DTO\StripeWebhookDTO;
use TresPontosTech\IntegrationStripe\Jobs\HandleStripeWebhookJob;
use TresPontosTech\IntegrationStripe\StripeApi;
use TresPontosTech\IntegrationStripe\Testing\FakeStripeApi;

beforeEach(function (): void {
    Event::fake([SubscriptionActivated::class]);
    $this->api = new FakeStripeApi;
    $this->api->subscriptions['sub_1'] = ['status' => 'active', 'metadata' => [], 'cancel_at_period_end' => false, 'cancel_at' => null];
    $this->app->instance(StripeApi::class, $this->api);

    User::factory()->create()->subscriptions()->create([
        'type' => 'stripe-gold',
        'stripe_id' => 'sub_1',
        'stripe_status' => 'active',
        'quantity' => 1,
    ]);
});

function stripeJob(): HandleStripeWebhookJob
{
    return new HandleStripeWebhookJob(new StripeWebhookDTO('evt_1', 'customer.subscription.updated', 'sub_1', 1_800_000_000));
}

it('processes an event once and drops the redelivery', function (): void {
    dispatch_sync(stripeJob());
    dispatch_sync(stripeJob());

    Event::assertDispatchedTimes(SubscriptionActivated::class, 1);
});

it('releases the event id when the work fails, so the retry runs', function (): void {
    $this->api->nextReadFails = new RuntimeException('Stripe down');

    expect(fn () => dispatch_sync(stripeJob()))->toThrow(RuntimeException::class);
    dispatch_sync(stripeJob());

    Event::assertDispatchedTimes(SubscriptionActivated::class, 1);
});
