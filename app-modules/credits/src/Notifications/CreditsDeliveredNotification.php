<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationTone;

/**
 * A compra de créditos foi paga e os créditos chegaram.
 */
final class CreditsDeliveredNotification extends ContentNotification
{
    public function __construct(private readonly int $quantity) {}

    public function content(): NotificationContent
    {
        return new NotificationContent(
            kind: NotificationKind::CreditsDelivered,
            title: __('credits::notifications.credits_delivered.title'),
            body: __('credits::notifications.credits_delivered.body', ['quantity' => $this->quantity]),
            tone: NotificationTone::Success,
        );
    }
}
