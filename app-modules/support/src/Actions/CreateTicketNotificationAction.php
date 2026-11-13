<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketNotificationChannelEnum;
use TresPontosTech\Support\Enums\TicketNotificationEventEnum;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketNotification;

final class CreateTicketNotificationAction
{
    public function execute(
        SupportTicket $ticket,
        TicketNotificationChannelEnum $channel,
        ?SupportTicketStatusEnum $from,
        SupportTicketStatusEnum $to,
        string $message,
    ): SupportTicketNotification {
        return $ticket->notifications()->create([
            'channel' => $channel,
            'event' => $from instanceof SupportTicketStatusEnum ? TicketNotificationEventEnum::StatusChanged : TicketNotificationEventEnum::Created,
            'from_status' => $from,
            'to_status' => $to,
            'message' => $message,
            'status' => TicketNotificationStatusEnum::Pending,
        ]);
    }
}
