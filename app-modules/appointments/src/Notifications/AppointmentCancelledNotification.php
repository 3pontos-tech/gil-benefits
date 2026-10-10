<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationTone;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O encontro foi cancelado. Cancelado em cima da hora, o texto avisa que o crédito foi consumido.
 */
final class AppointmentCancelledNotification extends ContentNotification
{
    public function __construct(
        private readonly Appointment $appointment,
        private readonly bool $isLate,
    ) {}

    public function content(): NotificationContent
    {
        $key = $this->isLate ? 'user_cancelled_late' : 'cancelled';

        return new NotificationContent(
            kind: $this->isLate ? NotificationKind::AppointmentCancelledLate : NotificationKind::AppointmentCancelled,
            title: __(sprintf('appointments::resources.appointments.notifications.%s.title', $key)),
            body: __(
                sprintf('appointments::resources.appointments.notifications.%s.body', $key),
                ['hours' => Appointment::CANCELLATION_WINDOW_HOURS],
            ),
            tone: NotificationTone::Warning,
            appointmentId: $this->appointment->id,
        );
    }
}
