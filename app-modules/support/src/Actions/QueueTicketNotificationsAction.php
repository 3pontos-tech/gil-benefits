<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Jobs\SendTicketNotificationJob;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\TicketNotificationManager;

final readonly class QueueTicketNotificationsAction
{
    public function __construct(
        private TicketNotificationManager $channels,
    ) {}

    public function execute(SupportTicket $ticket, ?SupportTicketStatusEnum $from, SupportTicketStatusEnum $to): void
    {
        foreach ($this->channels->registeredChannels() as $channel) {
            $message = $this->channels->channel($channel)->message($ticket, $from, $to);

            if ($message === null) {
                continue;
            }

            $notification = resolve(CreateTicketNotificationAction::class)->execute($ticket, $channel, $from, $to, $message);

            dispatch(new SendTicketNotificationJob($notification->id))->afterCommit();
        }
    }
}
