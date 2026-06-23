<?php

declare(strict_types=1);

use Basement\Webhooks\Models\InboundWebhook;
use Illuminate\Support\Facades\Bus;
use TresPontosTech\IntegrationMonday\Jobs\HandleMondayWebhookJob;

beforeEach(function (): void {
    config(['monday.webhook_secret' => 'secret']);
});

it('echoes the challenge during registration', function (): void {
    $this->postJson('/webhooks/monday?token=secret', ['challenge' => 'abc123'])
        ->assertOk()
        ->assertExactJson(['challenge' => 'abc123']);
});

it('rejects requests without the correct secret', function (): void {
    $this->postJson('/webhooks/monday?token=wrong', ['challenge' => 'abc123'])
        ->assertUnauthorized();

    $this->postJson('/webhooks/monday', ['challenge' => 'abc123'])
        ->assertUnauthorized();
});

it('queues the handler job and stores the inbound webhook', function (): void {
    Bus::fake();

    $payload = [
        'event' => [
            'type' => 'update_column_value',
            'boardId' => 111,
            'pulseId' => 987654,
            'columnId' => 'status',
            'value' => ['label' => ['index' => 0, 'text' => 'Em Andamento']],
        ],
    ];

    $this->postJson('/webhooks/monday?token=secret', $payload)->assertOk();

    Bus::assertDispatched(
        HandleMondayWebhookJob::class,
        fn (HandleMondayWebhookJob $job): bool => $job->payload['event']['pulseId'] === 987654,
    );

    expect(InboundWebhook::query()->where('source', 'monday')->where('event', 'update_column_value')->count())->toBe(1);
});
