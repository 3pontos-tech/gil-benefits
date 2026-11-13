<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Senders;

use Illuminate\Http\Client\ConnectionException;
use TresPontosTech\IntegrationChatx\ChatxStatusClient;
use TresPontosTech\IntegrationChatx\Support\ChatxMessage;
use TresPontosTech\IntegrationChatx\Support\ChatxNotificationPayload;
use TresPontosTech\Support\Contracts\TicketNotificationChannel;
use TresPontosTech\Support\DTOs\TicketNotificationResult;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketNotification;

final readonly class ChatxNotificationChannel implements TicketNotificationChannel
{
    public function __construct(
        private ChatxStatusClient $client,
    ) {}

    public function message(SupportTicket $ticket, ?SupportTicketStatusEnum $from, SupportTicketStatusEnum $to): ?string
    {
        if ($ticket->origin?->source !== TicketOriginSourceEnum::Chatx) {
            return null;
        }

        return $from instanceof SupportTicketStatusEnum
            ? ChatxMessage::statusChanged($ticket, $from, $to)
            : ChatxMessage::created($ticket);
    }

    public function send(SupportTicketNotification $notification): TicketNotificationResult
    {
        if (! $this->client->isConfigured()) {
            return TicketNotificationResult::skipped('CHATX_STATUS_WEBHOOK_URL or CHATX_STATUS_WEBHOOK_TOKEN is not set.');
        }

        try {
            $response = $this->client->post(ChatxNotificationPayload::from($notification));
        } catch (ConnectionException $connectionException) {
            return TicketNotificationResult::failed(null, $connectionException->getMessage());
        }

        return $response->successful()
            ? TicketNotificationResult::sent($response->status())
            : TicketNotificationResult::failed($response->status(), $response->body());
    }
}
