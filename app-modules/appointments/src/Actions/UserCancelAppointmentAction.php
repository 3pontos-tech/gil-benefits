<?php

namespace TresPontosTech\Appointments\Actions;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentUserCancelledLateMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

final class UserCancelAppointmentAction
{
    public function handle(Appointment $appointment): void
    {
        if ($appointment->user_id !== auth()->id()) {
            return;
        }

        $targetStatus = $this->resolveStatus($appointment);

        if (! $appointment->status->canTransitionTo($targetStatus)) {
            return;
        }

        $appointment->loadMissing(['user', 'consultant']);

        $appointment->update([
            'status' => $targetStatus,
            'cancelled_by' => auth()->id(),
            'cancellation_actor' => CancellationActor::User,
        ]);

        $appointment->user->forgetMonthlyAppointmentsLeftCache();

        $this->notify($appointment, $targetStatus);

        $mail = $targetStatus === AppointmentStatus::CancelledLate
            ? new AppointmentUserCancelledLateMail($appointment)
            : new AppointmentCancelledMail($appointment);

        Mail::to($appointment->user->email)->queue($mail);

        event(new AppointmentCancelled($appointment));

        Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $appointment->id)
            ->delete();

        if (filled($appointment->google_event_id)) {
            dispatch(new DeleteAppointmentCalendarEventJob($appointment));
        }
    }

    private function resolveStatus(Appointment $appointment): AppointmentStatus
    {
        $hoursUntilAppointment = now()->diffInHours($appointment->appointment_at, absolute: false);

        return $hoursUntilAppointment >= 24
            ? AppointmentStatus::Cancelled
            : AppointmentStatus::CancelledLate;
    }

    private function notify(Appointment $appointment, AppointmentStatus $targetStatus): void
    {
        $key = $targetStatus === AppointmentStatus::CancelledLate ? 'user_cancelled_late' : 'cancelled';

        Notification::make()
            ->title(__("appointments::resources.appointments.notifications.{$key}.title"))
            ->body(__("appointments::resources.appointments.notifications.{$key}.body"))
            ->warning()
            ->sendToDatabase($appointment->user)
            ->send();

        if (filled($appointment->consultant)) {
            Notification::make()
                ->title(__('appointments::resources.appointments.notifications.cancelled.title'))
                ->body(__('appointments::resources.appointments.notifications.cancelled_by_user.body', [
                    'name' => $appointment->user->name,
                ]))
                ->warning()
                ->sendToDatabase($appointment->consultant->user);
        }
    }
}
