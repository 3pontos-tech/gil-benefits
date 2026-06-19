<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;

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

it('dispatches a column-changed event from a status change payload', function (): void {
    Event::fake([MondayItemColumnChanged::class]);

    $this->postJson('/webhooks/monday?token=secret', [
        'event' => [
            'boardId' => 111,
            'pulseId' => 987654,
            'columnId' => 'status',
            'value' => ['label' => ['index' => 0, 'text' => 'Em andamento']],
        ],
    ])->assertOk();

    Event::assertDispatched(MondayItemColumnChanged::class, fn (MondayItemColumnChanged $event): bool => $event->itemId === '987654'
        && $event->columnId === 'status'
        && $event->index === 0);
});
