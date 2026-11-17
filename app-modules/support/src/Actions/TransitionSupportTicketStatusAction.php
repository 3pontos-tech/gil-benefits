<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;
use TresPontosTech\Support\Exceptions\InvalidTransitionException;
use TresPontosTech\Support\Mail\SupportTicketStatusUpdatedMail;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Single entry point for ticket status changes: every caller (admin, requester,
 * API, external destination) goes through the graph guard and the history.
 *
 * The caller states who made the change ($source, $actor) because the action
 * cannot tell an admin from a requester or an integration. $reason is only asked
 * for when an admin reopens a resolved ticket; the action stores whatever it gets.
 *
 * The status and its history row share one transaction, written here rather than
 * by a listener: a listener that failed before the history one ran left the status
 * changed with no row. The event fires after the commit, for the other modules.
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

    public function execute(
        SupportTicket $ticket,
        SupportTicketStatusEnum $to,
        TicketStatusChangeSourceEnum $source,
        ?User $actor = null,
        ?string $reason = null,
    ): SupportTicket {
        if (! $ticket->status->canTransitionTo($to)) {
            throw InvalidTransitionException::between($ticket->status, $to);
        }

        $from = $ticket->status;

        DB::transaction(function () use ($ticket, $from, $to, $source, $actor, $reason): void {
            $ticket->update(['status' => $to]);

            $ticket->statusChanges()->create([
                'from_status' => $from,
                'to_status' => $to,
                'source' => $source,
                'actor_user_id' => $actor?->getKey(),
                'reason' => $reason,
            ]);
        });

        event(new SupportTicketStatusChanged(
            ticket: $ticket,
            from: $from,
            to: $to,
            source: $source,
            actorUserId: $actor?->getKey(),
            reason: $reason,
        ));

        $requesterEmail = $ticket->getRequesterEmail();

        if (in_array($to, self::NOTIFIES, strict: true) && $requesterEmail !== null) {
            Mail::to($requesterEmail)->queue(new SupportTicketStatusUpdatedMail($ticket));
        }

        return $ticket;
    }
}
