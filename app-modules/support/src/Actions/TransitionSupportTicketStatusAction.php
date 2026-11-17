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
 *
 * The guard runs on the row locked inside that transaction, not on the instance the
 * caller passed: two changes arriving together (an agent and a webhook, or a webhook
 * delivered twice) would both pass a check on the status they read, writing a false
 * from_status or a move the graph forbids. $expectedFrom is for callers whose
 * decision only holds in one status (a customer answering a resolved ticket): the
 * change is refused if the ticket left it in the meantime, even when the graph
 * would allow the move from where it is now.
 *
 * The requester e-mail is queued before the event: a listener that throws would
 * otherwise drop it, and a retried request finds the status already changed and
 * never queues it again.
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
        ?SupportTicketStatusEnum $expectedFrom = null,
    ): SupportTicket {
        $from = DB::transaction(function () use ($ticket, $to, $source, $actor, $reason, $expectedFrom): SupportTicketStatusEnum {
            $locked = SupportTicket::query()
                ->withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($ticket->getKey());

            $from = $locked->status;

            if ($expectedFrom instanceof SupportTicketStatusEnum && $from !== $expectedFrom) {
                throw InvalidTransitionException::moved($expectedFrom, $from);
            }

            if (! $from->canTransitionTo($to)) {
                throw InvalidTransitionException::between($from, $to);
            }

            $locked->update(['status' => $to]);

            $locked->statusChanges()->create([
                'from_status' => $from,
                'to_status' => $to,
                'source' => $source,
                'actor_user_id' => $actor?->getKey(),
                'reason' => $reason,
            ]);

            return $from;
        });

        $ticket->refresh();

        $requesterEmail = $ticket->getRequesterEmail();

        if (in_array($to, self::NOTIFIES, strict: true) && $requesterEmail !== null) {
            Mail::to($requesterEmail)->queue(new SupportTicketStatusUpdatedMail($ticket));
        }

        event(new SupportTicketStatusChanged(
            ticket: $ticket,
            from: $from,
            to: $to,
            source: $source,
            actorUserId: $actor?->getKey(),
            reason: $reason,
        ));

        return $ticket;
    }
}
