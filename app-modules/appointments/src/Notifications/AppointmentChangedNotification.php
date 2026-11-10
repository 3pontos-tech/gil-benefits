<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationPush;
use App\Notifications\NotificationTone;
use TresPontosTech\Appointments\Enums\AppointmentChange;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Support\AppointmentMoment;

/**
 * A equipe mudou um encontro confirmado: o horário, o consultor (e com ele o link da sala), só o
 * link, ou o encontro voltou a aguardar confirmação. Vai ao celular na hora, porque sem ele a
 * pessoa vai no horário antigo ou entra por um link que não funciona mais. O `change` do payload
 * diz ao app qual foi.
 */
final class AppointmentChangedNotification extends ContentNotification
{
    public function __construct(
        private readonly Appointment $appointment,
        private readonly AppointmentChange $change,
    ) {}

    public function content(): NotificationContent
    {
        $title = __('appointments::resources.appointments.notifications.changed.title');
        $body = __(
            'appointments::resources.appointments.notifications.push.changed.' . $this->change->value,
            AppointmentMoment::of($this->appointment)->toReplacements(),
        );

        return new NotificationContent(
            kind: NotificationKind::AppointmentChanged,
            title: $title,
            body: $body,
            tone: $this->change === AppointmentChange::Unconfirmed ? NotificationTone::Warning : NotificationTone::Info,
            appointmentId: $this->appointment->id,
            push: new NotificationPush(
                title: __('appointments::resources.appointments.notifications.push.changed.title'),
                body: $body,
                urgent: true,
                expiresAt: $this->appointment->appointment_at,
                data: ['change' => $this->change->value],
            ),
        );
    }
}
