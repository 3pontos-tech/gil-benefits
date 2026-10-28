<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationTone;

final class CreditOrderCreatedNotification extends ContentNotification
{
    public function __construct(private readonly int $quantity) {}

    public function content(): NotificationContent
    {
        return new NotificationContent(
            kind: NotificationKind::CreditOrderCreated,
            title: __('credits::notifications.credit_order_created.title'),
            body: __('credits::notifications.credit_order_created.body', ['quantity' => $this->quantity]),
            tone: NotificationTone::Warning,
        );
    }
}
