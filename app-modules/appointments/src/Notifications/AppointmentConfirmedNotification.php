<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationPush;
use App\Notifications\NotificationTone;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Support\AppointmentMoment;

/**
 * O encontro pedido virou um horário marcado com consultor. Vai ao celular na hora: até aqui era
 * só um pedido, e a pessoa precisa reservar o horário (que o admin pode ter mudado ao confirmar).
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
            push: new NotificationPush(
                title: __('appointments::resources.appointments.notifications.push.confirmed.title'),
                body: __('appointments::resources.appointments.notifications.push.confirmed.body', AppointmentMoment::of($this->appointment)->toReplacements()),
                urgent: true,
                expiresAt: $this->appointment->appointment_at,
            ),
        );
    }
}
