<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationTone;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O encontro ganhou consultor e está confirmado.
 */
final class AppointmentConfirmedNotification extends ContentNotification
{
    public function __construct(private readonly Appointment $appointment) {}

    public function content(): NotificationContent
    {
        return new NotificationContent(
            kind: NotificationKind::AppointmentConfirmed,
            title: __('appointments::resources.appointments.notifications.scheduled.title'),
            body: __('appointments::resources.appointments.notifications.scheduled.body'),
            tone: NotificationTone::Success,
            appointmentId: $this->appointment->id,
        );
    }
}
