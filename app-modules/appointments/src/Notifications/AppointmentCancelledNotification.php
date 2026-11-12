<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationPush;
use App\Notifications\NotificationTone;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Support\AppointmentMoment;

/**
 * O encontro foi cancelado. Só vai ao celular quando foi a equipe: quem cancelou o próprio
 * encontro já sabe, e o cancelamento do sistema acontece um dia depois do horário, quando não há
 * mais nada a fazer. Nos dois casos a linha no sino continua.
 */
final class AppointmentCancelledNotification extends ContentNotification
{
    public function __construct(
        private readonly Appointment $appointment,
        private readonly bool $isLate,
        private readonly ?CancellationActor $cancelledBy = null,
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
            push: new NotificationPush(
                title: __('appointments::resources.appointments.notifications.push.cancelled.title'),
                body: __('appointments::resources.appointments.notifications.push.cancelled.body', AppointmentMoment::of($this->appointment)->toReplacements()),
                urgent: true,
                expiresAt: AppointmentMoment::endOf($this->appointment),
            ),
        );
    }

    protected function shouldPush(object $notifiable): bool
    {
        return $this->cancelledBy === CancellationActor::Admin;
    }
}
