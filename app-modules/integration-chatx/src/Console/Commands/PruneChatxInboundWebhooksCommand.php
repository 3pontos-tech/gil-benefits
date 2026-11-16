<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Console\Commands;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Models\InboundWebhook;
use Illuminate\Console\Command;

/**
 * ChatX deliveries carry the requester's name, e-mail and free-text description,
 * and the vendor model has no pruning of its own, so they would otherwise be kept
 * forever. The ticket keeps what it needs; the delivery is only for debugging.
 */
final class PruneChatxInboundWebhooksCommand extends Command
{
    protected $signature = 'chatx:prune-inbound-webhooks';

    protected $description = 'Delete stored ChatX webhook deliveries older than the retention window';

    public function handle(): int
    {
        $days = (int) config('chatx.inbound_webhook_retention_days');

        $deleted = InboundWebhook::query()
            ->where('source', InboundWebhookSourceEnum::Chatx->value)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info(sprintf('Deleted %d ChatX webhook deliveries older than %d days.', $deleted, $days));

        return self::SUCCESS;
    }
}
