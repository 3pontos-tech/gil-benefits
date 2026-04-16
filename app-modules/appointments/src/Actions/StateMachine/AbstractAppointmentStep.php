<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
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
        $this->appointment->loadMissing(['user', 'consultant']);

        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.cancelled.title'))
            ->body(__('appointments::resources.appointments.notifications.cancelled.body'))
            ->warning()
            ->sendToDatabase($this->appointment->user)
            ->send();

        Mail::to($this->appointment->user->email)->send(new AppointmentCancelledMail($this->appointment));

        $this->appointment->update([
            'status' => AppointmentStatus::Cancelled,
        ]);

        Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $this->appointment->id)
            ->delete();

        if (filled($this->appointment->google_event_id)) {
            dispatch(new DeleteAppointmentCalendarEventJob($this->appointment));
        }
    }
}
