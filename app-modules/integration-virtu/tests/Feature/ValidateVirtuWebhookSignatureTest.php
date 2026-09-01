<?php

declare(strict_types=1);

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\IntegrationVirtu\Jobs\HandleVirtuWebhookJob;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\call;

beforeEach(function (): void {
    config(['virtu.webhook_secret' => str_repeat('a1', 32)]);

    Queue::fake();
});

function postSignedVirtuWebhook(string $body, ?string $signature): TestResponse
{
    return call('POST', route('webhooks.virtu'), [], [], [], array_filter([
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
    ]), $body);
}

function signVirtuBody(string $body): string
{
    return hash_hmac('sha256', $body, str_repeat('a1', 32));
}

it('accepts a request whose signature matches the raw body', function (): void {
    $body = json_encode(['event' => 'TRANSACTION', 'data' => ['saleId' => 1003]], JSON_THROW_ON_ERROR);

    postSignedVirtuWebhook($body, signVirtuBody($body))->assertOk();

    Queue::assertPushed(HandleVirtuWebhookJob::class);
});

it('rejects a tampered body', function (): void {
    $body = json_encode(['event' => 'TRANSACTION', 'data' => ['saleId' => 1003]], JSON_THROW_ON_ERROR);
    $signature = signVirtuBody($body);

    $tampered = json_encode(['event' => 'TRANSACTION', 'data' => ['saleId' => 9999]], JSON_THROW_ON_ERROR);

    postSignedVirtuWebhook($tampered, $signature)->assertStatus(Response::HTTP_UNAUTHORIZED);

    Queue::assertNothingPushed();
});

it('rejects a missing signature header', function (): void {
    $body = json_encode(['event' => 'TRANSACTION'], JSON_THROW_ON_ERROR);

    postSignedVirtuWebhook($body, null)->assertStatus(Response::HTTP_UNAUTHORIZED);
});

it('rejects everything when no secret is configured', function (): void {
    config(['virtu.webhook_secret' => null]);

    $body = json_encode(['event' => 'TRANSACTION'], JSON_THROW_ON_ERROR);

    postSignedVirtuWebhook($body, signVirtuBody($body))->assertStatus(Response::HTTP_UNAUTHORIZED);
});

it('validates the raw body, not a re-encoded version of it', function (): void {
    // Key order, spacing and unicode escaping all survive here but would change
    // under json_encode(request->all()) — the classic way HMAC checks silently
    // stop matching.
    $body = '{"data":{"customer":{"name":"João Silva"}},  "event":"TRANSACTION"}';

    postSignedVirtuWebhook($body, signVirtuBody($body))->assertOk();
});

it('stores the raw payload before dispatching the job', function (): void {
    $body = json_encode(['event' => 'TRANSACTION', 'data' => ['saleId' => 1003]], JSON_THROW_ON_ERROR);

    // The job burns the idempotency key, so persisting has to win the race: if it
    // failed after dispatching, Virtu would redeliver and the redelivery would be
    // dropped as already processed, leaving nothing to reconcile from.
    app()->bind(StoreInboundWebhook::class, function (): never {
        throw new RuntimeException('storage down');
    });

    postSignedVirtuWebhook($body, signVirtuBody($body))->assertStatus(500);

    Queue::assertNotPushed(HandleVirtuWebhookJob::class);
});

it('records the inbound webhook alongside the job', function (): void {
    $body = json_encode(['event' => 'TRANSACTION', 'data' => ['saleId' => 1003]], JSON_THROW_ON_ERROR);

    postSignedVirtuWebhook($body, signVirtuBody($body))->assertOk();

    Queue::assertPushed(HandleVirtuWebhookJob::class);
    assertDatabaseHas('inbound_webhooks', ['source' => InboundWebhookSourceEnum::Virtu->value]);
});
