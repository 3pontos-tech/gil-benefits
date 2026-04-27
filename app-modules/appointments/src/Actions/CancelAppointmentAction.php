<?php

namespace TresPontosTech\Appointments\Actions;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

final class CancelAppointmentAction
{
    public function handle(Appointment $appointment): void
    {
        if (! $appointment->status->canTransitionTo(AppointmentStatus::Cancelled)) {
            return;
        }

        $appointment->loadMissing(['user', 'consultant']);

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_by' => auth()->id(),
            'cancellation_actor' => CancellationActor::Admin,
        ]);

        $appointment->user->forgetMonthlyAppointmentsLeftCache();

        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.cancelled.title'))
            ->body(__('appointments::resources.appointments.notifications.cancelled.body'))
            ->warning()
            ->sendToDatabase($appointment->user)
            ->send();

        if (filled($appointment->consultant)) {
            Notification::make()
                ->title(__('appointments::resources.appointments.notifications.cancelled.title'))
                ->body(__('appointments::resources.appointments.notifications.cancelled_by_admin.body', [
                    'name' => $appointment->user->name,
                ]))
                ->warning()
                ->sendToDatabase($appointment->consultant->user);
        }

        Mail::to($appointment->user->email)->queue(new AppointmentCancelledMail($appointment));

        event(new AppointmentCancelled($appointment));

        Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $appointment->id)
            ->delete();

        if (filled($appointment->google_event_id)) {
            dispatch(new DeleteAppointmentCalendarEventJob($appointment));
        }
    }
}
