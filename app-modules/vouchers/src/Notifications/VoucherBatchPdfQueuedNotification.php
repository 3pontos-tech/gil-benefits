<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationTone;

final class VoucherBatchPdfQueuedNotification extends ContentNotification
{
    public function content(): NotificationContent
    {
        return new NotificationContent(
            kind: NotificationKind::VoucherBatchPdfQueued,
            title: __('vouchers::vouchers.pdf.queued_title'),
            body: __('vouchers::vouchers.pdf.queued_body'),
            tone: NotificationTone::Info,
        );
    }
}
