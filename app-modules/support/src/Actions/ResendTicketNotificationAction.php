<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use Throwable;
use TresPontosTech\Support\Jobs\SendTicketNotificationJob;
use TresPontosTech\Support\Models\SupportTicketNotification;

final class ResendTicketNotificationAction
{
    /**
     * requeue() commits `pending` before the job is queued. If queueing fails, the row
     * goes back to `failed`: a `pending` row with no job behind it would never be sent,
     * and Resend only shows on failed rows, so nobody could retry it.
     *
     * @throws Throwable
     */
    public function execute(SupportTicketNotification $notification): void
    {
        if (! $notification->requeue()) {
            return;
        }

        try {
            dispatch(new SendTicketNotificationJob($notification->id));
        } catch (Throwable $throwable) {
            SupportTicketNotification::query()->whereKey($notification->id)->failPending();

            throw $throwable;
        }
    }
}
