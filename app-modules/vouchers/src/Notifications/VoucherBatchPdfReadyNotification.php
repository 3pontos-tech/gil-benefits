<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationLink;
use App\Notifications\NotificationTone;

final class VoucherBatchPdfReadyNotification extends ContentNotification
{
    public function __construct(
        private readonly string $batchName,
        private readonly string $downloadUrl,
    ) {}

    public function content(): NotificationContent
    {
        return new NotificationContent(
            kind: NotificationKind::VoucherBatchPdfReady,
            title: __('vouchers::vouchers.pdf.ready_title'),
            body: __('vouchers::vouchers.pdf.ready_body', ['batch' => $this->batchName]),
            tone: NotificationTone::Success,
            link: new NotificationLink(__('vouchers::vouchers.pdf.download'), $this->downloadUrl),
        );
    }
}
