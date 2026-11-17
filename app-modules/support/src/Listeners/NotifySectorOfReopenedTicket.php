<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Listeners;

use App\Models\Users\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;
use TresPontosTech\Support\Mail\SupportTicketReopenedMail;

/**
 * The sector only hears about a ticket when it is opened, so a reopened ticket
 * would go unnoticed. The e-mail goes to the same sectors the category routes to
 * on creation. A reopen reported by the external destination is skipped: the
 * team reopened it there and already knows.
 */
final class NotifySectorOfReopenedTicket
{
    public function handle(SupportTicketStatusChanged $event): void
    {
        if (! $event->from->isReopenedBy($event->to)
            || $event->source === TicketStatusChangeSourceEnum::ExternalDestination) {
            return;
        }

        $recipients = collect($event->ticket->category->destinationChannels())
            ->map(fn (TicketDestinationChannelEnum $channel): ?string => $channel->getRecipientEmail())
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            Log::warning('Reopened ticket has no sector e-mail to notify.', [
                'ticket_id' => $event->ticket->id,
                'category' => $event->ticket->category->value,
            ]);

            return;
        }

        $reopenedBy = $event->actorUserId === null
            ? null
            : User::query()->whereKey($event->actorUserId)->value('name');

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new SupportTicketReopenedMail($event->ticket, $event->reason, $reopenedBy));
        }
    }
}
