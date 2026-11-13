<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Contracts;

use TresPontosTech\Support\DTOs\TicketNotificationResult;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketNotification;

/**
 * Tells the requester about their ticket through one channel (WhatsApp, ...). One
 * implementation per channel. Composing and sending only — no persistence.
 */
interface TicketNotificationChannel
{
    /**
     * Null when this channel does not notify the ticket or the transition.
     */
    public function message(SupportTicket $ticket, ?SupportTicketStatusEnum $from, SupportTicketStatusEnum $to): ?string;

    public function send(SupportTicketNotification $notification): TicketNotificationResult;
}
