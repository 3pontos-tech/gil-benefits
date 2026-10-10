<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationTone;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O encontro foi realizado.
 */
final class AppointmentCompletedNotification extends ContentNotification
{
    public function __construct(private readonly Appointment $appointment) {}

    public function content(): NotificationContent
    {
        return new NotificationContent(
            kind: NotificationKind::AppointmentCompleted,
            title: __('appointments::resources.appointments.notifications.completed.title'),
            body: __('appointments::resources.appointments.notifications.completed.body'),
            tone: NotificationTone::Success,
            appointmentId: $this->appointment->id,
        );
    }
}
