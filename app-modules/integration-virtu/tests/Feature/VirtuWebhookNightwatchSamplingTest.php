<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Laravel\Nightwatch\Facades\Nightwatch;

use function Pest\Laravel\call;

beforeEach(function (): void {
    config(['virtu.webhook_secret' => str_repeat('a1', 32)]);

    Queue::fake();
    Nightwatch::dontSample();
});

it('always sends the payment notice to Nightwatch, along with the jobs it dispatches', function (): void {
    $body = json_encode(['event' => 'TRANSACTION', 'data' => ['saleId' => 1003]], JSON_THROW_ON_ERROR);

    call('POST', route('webhooks.virtu'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $body, str_repeat('a1', 32)),
    ], $body)->assertOk();

    expect(Nightwatch::sampling())->toBeTrue()
        ->and(context()->getHidden('nightwatch_should_sample'))->toBeTrue();
});

it('sends the payment notice to Nightwatch even when its signature is rejected', function (): void {
    call('POST', route('webhooks.virtu'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WEBHOOK_SIGNATURE' => 'forged',
    ], '{"event":"TRANSACTION"}')->assertUnauthorized();

    expect(Nightwatch::sampling())->toBeTrue();
});
