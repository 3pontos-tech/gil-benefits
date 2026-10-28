<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationTone;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;

final class AppointmentCancelledForConsultantNotification extends ContentNotification
{
    public function __construct(
        private readonly Appointment $appointment,
        private readonly CancellationActor $actor,
    ) {}

    public function content(): NotificationContent
    {
        $actorKey = $this->actor === CancellationActor::Admin ? 'cancelled_by_admin' : 'cancelled_by_user';

        return new NotificationContent(
            kind: NotificationKind::AppointmentCancelledForConsultant,
            title: __('appointments::resources.appointments.notifications.cancelled.title'),
            body: __(
                sprintf('appointments::resources.appointments.notifications.%s.body', $actorKey),
                ['name' => $this->appointment->user->name],
            ),
            tone: NotificationTone::Warning,
            appointmentId: $this->appointment->id,
        );
    }
}
