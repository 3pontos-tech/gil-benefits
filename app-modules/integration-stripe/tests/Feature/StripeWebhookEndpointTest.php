<?php

declare(strict_types=1);

use App\Enums\InboundWebhookSourceEnum;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use TresPontosTech\IntegrationStripe\Jobs\HandleStripeWebhookJob;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\call;

beforeEach(function (): void {
    config(['stripe.webhook_secret' => 'whsec_test', 'stripe.webhook_tolerance' => 300]);
    Queue::fake();
});

function stripeWebhookBody(): string
{
    return (string) json_encode([
        'id' => 'evt_1',
        'object' => 'event',
        'type' => 'customer.subscription.updated',
        'created' => 1_800_000_000,
        'data' => ['object' => ['id' => 'sub_1', 'object' => 'subscription']],
    ]);
}

function postStripeWebhook(string $body, ?string $signature): TestResponse
{
    return call('POST', route('webhooks.stripe'), server: array_filter([
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ]), content: $body);
}

function stripeSignature(string $body, string $secret, int $timestamp): string
{
    return sprintf('t=%d,v1=%s', $timestamp, hash_hmac('sha256', $timestamp . '.' . $body, $secret));
}

it('accepts a signed event, stores it and queues it', function (): void {
    $body = stripeWebhookBody();

    postStripeWebhook($body, stripeSignature($body, 'whsec_test', Date::now()->getTimestamp()))->assertOk();

    assertDatabaseHas('inbound_webhooks', ['source' => InboundWebhookSourceEnum::Stripe->value]);
    Queue::assertPushed(HandleStripeWebhookJob::class, fn (HandleStripeWebhookJob $job): bool => $job->dto->eventId === 'evt_1'
        && $job->dto->type === 'customer.subscription.updated'
        && $job->dto->objectId === 'sub_1');
});

it('refuses an event that is not signed with our secret', function (?string $signature): void {
    postStripeWebhook(stripeWebhookBody(), $signature)->assertUnauthorized();

    Queue::assertNothingPushed();
})->with([
    'no signature' => [null],
    'other secret' => fn (): string => stripeSignature(stripeWebhookBody(), 'whsec_other', Date::now()->getTimestamp()),
    'too old' => fn (): string => stripeSignature(stripeWebhookBody(), 'whsec_test', Date::now()->subHours(1)->getTimestamp()),
]);

it('refuses every event while the secret is not configured', function (): void {
    config(['stripe.webhook_secret' => null]);
    $body = stripeWebhookBody();

    postStripeWebhook($body, stripeSignature($body, '', Date::now()->getTimestamp()))->assertUnauthorized();
});
