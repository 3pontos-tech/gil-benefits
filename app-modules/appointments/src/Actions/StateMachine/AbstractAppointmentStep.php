<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

abstract class AbstractAppointmentStep
{
    public function __construct(
        public Appointment $appointment,
    ) {}

    public function handle(): void
    {
        $this->processStep();

        $this->notify();
    }

    abstract public function processStep(): void;

    abstract public function notify(): void;

    public function cancel(): void
    {
        if ($this->appointment->status === AppointmentStatus::Cancelled) {
            return;
        }

        $this->appointment->loadMissing(['user', 'consultant']);

        $this->appointment->update([
            'status' => AppointmentStatus::Cancelled,
        ]);

        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.cancelled.title'))
            ->body(__('appointments::resources.appointments.notifications.cancelled.body'))
            ->warning()
            ->sendToDatabase($this->appointment->user)
            ->send();

        Mail::to($this->appointment->user->email)->queue(new AppointmentCancelledMail($this->appointment));

        event(new AppointmentCancelled($this->appointment));

        Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $this->appointment->id)
            ->delete();

        if (filled($this->appointment->google_event_id)) {
            dispatch(new DeleteAppointmentCalendarEventJob($this->appointment));
        }
    }
}
