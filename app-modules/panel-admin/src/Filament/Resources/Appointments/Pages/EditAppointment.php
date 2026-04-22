<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Throwable;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var Appointment $appointment */
        $appointment = $this->record;

        if ($appointment->wasChanged('consultant_id')) {
            try {
                resolve(AssignConsultantAction::class)->handle($appointment);
            } catch (SlotUnavailableException $exception) {
                Notification::make()
                    ->title(__('appointments::resources.appointments.exceptions.consultant_unavailable'))
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        if ($appointment->wasChanged('appointment_at') && filled($appointment->consultant_id)) {
            try {
                resolve(AssignConsultantAction::class)->handle($appointment);
            } catch (SlotUnavailableException $exception) {
                Notification::make()
                    ->title(__('appointments::resources.appointments.exceptions.consultant_unavailable'))
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        if ($appointment->wasChanged('status')) {
            $this->handleStatusChange($appointment);
        }
    }

    private function handleStatusChange(Appointment $appointment): void
    {
        if ($appointment->status === AppointmentStatus::Active) {
            $appointment->loadMissing(['consultant', 'user']);
            $consultant = $appointment->consultant;

            if (filled($consultant) && filled($consultant->email) && blank($appointment->google_event_id)) {
                try {
                    dispatch_sync(new CreateAppointmentCalendarEventJob($appointment));

                    $appointment->refresh();
                    $this->refreshFormData(['meeting_url', 'google_event_id']);
                } catch (Throwable) {
                    Notification::make()
                        ->title(__('appointments::resources.appointments.exceptions.calendar_event_failed'))
                        ->danger()
                        ->send();
                }
            }

            if (filled($consultant) && filled($consultant->email)) {
                Mail::to($consultant->email)->queue(new AppointmentScheduledMail($appointment));
            }
        }

        if ($appointment->status === AppointmentStatus::Completed) {
            $appointment->loadMissing(['user', 'consultant']);

            if (filled($appointment->user) && filled($appointment->user->email)) {
                Mail::to($appointment->user->email)->queue(new AppointmentCompletedMail($appointment));
            }
        }

        if ($appointment->status === AppointmentStatus::Cancelled) {
            $appointment->loadMissing(['user', 'consultant']);

            Schedule::query()
                ->where('schedule_type', ScheduleTypes::APPOINTMENT)
                ->whereJsonContains('metadata->appointment_id', $appointment->id)
                ->delete();

            if (filled($appointment->google_event_id)) {
                dispatch(new DeleteAppointmentCalendarEventJob($appointment));
            }

            if (filled($appointment->user) && filled($appointment->user->email)) {
                Mail::to($appointment->user->email)->queue(new AppointmentCancelledMail($appointment));
            }
        }
    }
}
