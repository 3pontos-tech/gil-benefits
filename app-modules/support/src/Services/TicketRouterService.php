<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Services;

use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Mail\SupportTicketConfirmationMail;
use TresPontosTech\Support\Mail\SupportTicketMail;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

class TicketRouterService
{
    public function dispatch(SupportTicket $ticket): void
    {
        $channel = $ticket->category->getDestinationChannel();
        $type = $channel->getDestinationType();

        $destination = TicketDestination::query()->create([
            'support_ticket_id' => $ticket->id,
            'type' => $type,
            'channel' => $channel,
            'status' => 'pending',
        ]);

        match ($type) {
            TicketDestinationTypeEnum::Email => $this->routeEmail($ticket, $destination),
            TicketDestinationTypeEnum::Monday => $this->routeMonday($destination),
        };

        $ticket->update(['status' => SupportTicketStatusEnum::Dispatched]);
    }

    private function routeEmail(SupportTicket $ticket, TicketDestination $destination): void
    {
        $recipient = $destination->channel->getRecipientEmail();

        $sent = Mail::to($recipient)->send(new SupportTicketMail($ticket));

        $destination->update([
            'status' => 'sent',
            'reference_id' => method_exists($sent, 'getMessageId') ? $sent->getMessageId() : null,
        ]);

        $requesterEmail = $ticket->getRequesterEmail();
        if ($requesterEmail !== null) {
            Mail::to($requesterEmail)->send(new SupportTicketConfirmationMail($ticket));
        }
    }

    private function routeMonday(TicketDestination $destination): void
    {
        // fase 2 — Monday integration not implemented
        $destination->update(['status' => 'pending']);
    }
}
