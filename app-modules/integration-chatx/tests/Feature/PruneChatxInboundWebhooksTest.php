<?php

declare(strict_types=1);

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Enums\InboundWebhookStatus;
use Basement\Webhooks\Models\InboundWebhook;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelBack;
use function Pest\Laravel\travelTo;

function storeDelivery(InboundWebhookSourceEnum $source, int $daysAgo): InboundWebhook
{
    travelTo(now()->subDays($daysAgo));

    $webhook = InboundWebhook::query()->create([
        'source' => $source->value,
        'event' => 'ticket.created',
        'url' => 'https://example.test/webhooks/chatx',
        'payload' => ['event' => 'ticket.created'],
        'status' => InboundWebhookStatus::Pending,
    ]);

    travelBack();

    return $webhook;
}

it('deletes ChatX deliveries older than the retention window', function (): void {
    config()->set('chatx.inbound_webhook_retention_days', 90);

    $old = storeDelivery(InboundWebhookSourceEnum::Chatx, 91);
    $recent = storeDelivery(InboundWebhookSourceEnum::Chatx, 89);

    artisan('chatx:prune-inbound-webhooks')->assertSuccessful();

    expect(InboundWebhook::query()->find($old->id))->toBeNull()
        ->and(InboundWebhook::query()->find($recent->id))->not->toBeNull();
});

it('leaves deliveries from other sources alone', function (): void {
    config()->set('chatx.inbound_webhook_retention_days', 90);

    $other = collect(InboundWebhookSourceEnum::cases())
        ->first(fn (InboundWebhookSourceEnum $source): bool => $source !== InboundWebhookSourceEnum::Chatx);

    $kept = storeDelivery($other, 365);

    artisan('chatx:prune-inbound-webhooks')->assertSuccessful();

    expect(InboundWebhook::query()->find($kept->id))->not->toBeNull();
});
