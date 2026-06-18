<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Exceptions\InvalidTransitionException;
use TresPontosTech\Support\Mail\SupportTicketStatusUpdatedMail;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Single entry point for manual ticket status changes. Guards the transition
 * against the status graph and notifies the requester when the ticket is
 * resolved or closed. Used by both the admin status action and the requester's
 * "close" action.
 */
class TransitionSupportTicketStatusAction
{
    /**
     * Statuses that notify the requester by e-mail when reached.
     */
    private const NOTIFIES = [
        SupportTicketStatusEnum::InProgress,
        SupportTicketStatusEnum::Resolved,
        SupportTicketStatusEnum::Closed,
    ];

    public function execute(SupportTicket $ticket, SupportTicketStatusEnum $to): SupportTicket
    {
        if (! $ticket->status->canTransitionTo($to)) {
            throw InvalidTransitionException::between($ticket->status, $to);
        }

        $ticket->update(['status' => $to]);

        $requesterEmail = $ticket->getRequesterEmail();

        if (in_array($to, self::NOTIFIES, strict: true) && $requesterEmail !== null) {
            Mail::to($requesterEmail)->queue(new SupportTicketStatusUpdatedMail($ticket));
        }

        return $ticket;
    }
}
