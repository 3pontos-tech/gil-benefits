<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationTone;

final class VoucherBatchPdfFailedNotification extends ContentNotification
{
    public function content(): NotificationContent
    {
        return new NotificationContent(
            kind: NotificationKind::VoucherBatchPdfFailed,
            title: __('vouchers::vouchers.pdf.failed_title'),
            body: __('vouchers::vouchers.pdf.failed_body'),
            tone: NotificationTone::Danger,
        );
    }
}
