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
 * O lembrete de 1 h antes, que o app e o Figma prometem. Só push: no sino ele viraria uma lista de
 * lembretes vencidos, e a tela inicial já mostra a contagem regressiva. Um lembrete que não chega
 * antes do encontro começar é descartado pela Expo.
 */
final class AppointmentReminderNotification extends ContentNotification
{
    public function __construct(private readonly Appointment $appointment) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->withPush($notifiable, []);
    }

    public function content(): NotificationContent
    {
        $title = __('appointments::resources.appointments.notifications.push.reminder.title');
        $body = __('appointments::resources.appointments.notifications.push.reminder.body', AppointmentMoment::of($this->appointment)->toReplacements());

        return new NotificationContent(
            kind: NotificationKind::AppointmentReminder,
            title: $title,
            body: $body,
            tone: NotificationTone::Info,
            appointmentId: $this->appointment->id,
            push: new NotificationPush(
                title: $title,
                body: $body,
                urgent: true,
                expiresAt: $this->appointment->appointment_at,
            ),
        );
    }
}
