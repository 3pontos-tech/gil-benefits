<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Senders;

use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\DTOs\DispatchResult;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Mail\SupportTicketMail;
use TresPontosTech\Support\Models\SupportTicket;

final class EmailTicketSender implements TicketChannelSender
{
    public function send(SupportTicket $ticket, TicketDestinationChannelEnum $channel): DispatchResult
    {
        $recipient = $channel->getRecipientEmail();

        if ($recipient === null) {
            return DispatchResult::failed(sprintf('No recipient configured for channel %s.', $channel->value));
        }

        $sent = Mail::to($recipient)->sendNow(new SupportTicketMail($ticket));

        return DispatchResult::sent($sent?->getMessageId());
    }
}
