<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use TresPontosTech\Support\Jobs\SendTicketNotificationJob;
use TresPontosTech\Support\Models\SupportTicketNotification;

final class ResendTicketNotificationAction
{
    public function execute(SupportTicketNotification $notification): void
    {
        if (! $notification->requeue()) {
            return;
        }

        dispatch(new SendTicketNotificationJob($notification->id));
    }
}
