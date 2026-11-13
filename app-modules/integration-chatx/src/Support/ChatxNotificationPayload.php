<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Support;

use TresPontosTech\Support\Enums\TicketNotificationEventEnum;
use TresPontosTech\Support\Models\SupportTicketNotification;

final class ChatxNotificationPayload
{
    /**
     * @return array{
     *     event: string,
     *     timestamp: string,
     *     external_reference: string|null,
     *     ticket: array{id: string, protocol: string, status: string, previous_status: string|null, subject: string},
     *     customer: array{client_id: string|null, name: string|null, phone: string|null, email: string|null},
     *     message: string,
     * }
     */
    public static function from(SupportTicketNotification $notification): array
    {
        $ticket = $notification->ticket->loadMissing(['origin', 'user.detail']);

        return [
            'event' => $notification->event === TicketNotificationEventEnum::Created ? 'ticket.created' : 'ticket.status_updated',
            'timestamp' => now()->toIso8601ZuluString(),
            'external_reference' => $ticket->origin?->external_reference,
            'ticket' => [
                'id' => $ticket->id,
                'protocol' => $ticket->protocol,
                'status' => $notification->to_status->value,
                'previous_status' => $notification->from_status?->value,
                'subject' => $ticket->subject,
            ],
            'customer' => [
                'client_id' => $ticket->user_id,
                'name' => $ticket->getRequesterName(),
                'phone' => $ticket->user?->detail?->phone_number,
                'email' => $ticket->getRequesterEmail(),
            ],
            'message' => $notification->message,
        ];
    }
}
