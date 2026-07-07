<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\DeleteCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\AppointmentResource;

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

        $consultantChanged = $appointment->wasChanged('consultant_id');
        $timeChanged = $appointment->wasChanged('appointment_at');

        if (blank($appointment->consultant_id) || (! $consultantChanged && ! $timeChanged)) {
            return;
        }

        try {
            resolve(AssignConsultantAction::class)->handle($appointment);
        } catch (SlotUnavailableException) {
            Notification::make()
                ->title(__('appointments::resources.appointments.exceptions.consultant_unavailable'))
                ->danger()
                ->send();

            $this->halt();
        }

        if ($consultantChanged) {
            $this->moveCalendarEvent($appointment);
        }
    }

    /**
     * Move the Google Calendar event when the consultant changes: drop it from the
     * previous consultant's calendar (which also removes it from the attendee's) and
     * recreate it on the new consultant's calendar, refreshing the meeting link.
     */
    private function moveCalendarEvent(Appointment $appointment): void
    {
        $previousConsultantId = $appointment->getOriginal('consultant_id');

        if (filled($appointment->google_event_id) && filled($previousConsultantId)) {
            $previousConsultant = Consultant::query()->find($previousConsultantId);

            if ($previousConsultant instanceof Consultant && filled($previousConsultant->email)) {
                try {
                    resolve(DeleteCalendarEventAction::class)->handle($appointment, $previousConsultant->email);
                } catch (Throwable) {
                    Notification::make()
                        ->title(__('panel-admin::resources.appointments.actions.calendar_event_delete_failed'))
                        ->warning()
                        ->send();
                }
            }
        }

        $appointment->loadMissing('consultant');
        $consultant = $appointment->consultant;

        if ($appointment->status === AppointmentStatus::Active
            && filled($consultant)
            && filled($consultant->email)
            && blank($appointment->google_event_id)) {
            try {
                dispatch_sync(new CreateAppointmentCalendarEventJob($appointment));
            } catch (Throwable) {
                Notification::make()
                    ->title(__('panel-admin::resources.appointments.actions.calendar_event_failed'))
                    ->warning()
                    ->send();
            }
        }
    }
}
