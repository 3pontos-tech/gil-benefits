<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Throwable;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Mail\AppointmentConsultantUnassignedMail;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\DeleteCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\AppointmentResource;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected ?string $previousConsultantId = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Capture the current consultant before the record is filled and saved:
        // by the time afterSave() runs, getOriginal() has already synced to the new value.
        /** @var Appointment $appointment */
        $appointment = $this->record;
        $this->previousConsultantId = $appointment->consultant_id;

        return $data;
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
            $this->moveCalendarEvent($appointment, $this->previousConsultantId);
        }
    }

    /**
     * Move the appointment when the consultant changes: drop the Google Calendar event
     * from the previous consultant (and the attendee), recreate it on the new consultant
     * with a fresh meeting link, and notify both consultants — mirroring the confirmation
     * flow for the new one and warning the previous one that it left their agenda.
     */
    private function moveCalendarEvent(Appointment $appointment, ?string $previousConsultantId): void
    {
        $previousConsultant = filled($previousConsultantId)
            ? Consultant::query()->find($previousConsultantId)
            : null;

        // Remove the event (and Meet link) from the previous consultant's calendar,
        // which also drops it from the attendee's calendar.
        if (filled($appointment->google_event_id)
            && $previousConsultant instanceof Consultant
            && filled($previousConsultant->email)) {
            try {
                resolve(DeleteCalendarEventAction::class)->handle($appointment, $previousConsultant->email);
            } catch (Throwable) {
                Notification::make()
                    ->title(__('panel-admin::resources.appointments.actions.calendar_event_delete_failed'))
                    ->warning()
                    ->send();
            }
        }

        // Only confirmed (Active) appointments have a booked agenda to move and consultants to notify.
        if ($appointment->status !== AppointmentStatus::Active) {
            return;
        }

        // Warn the previous consultant that the appointment left their agenda.
        if ($previousConsultant instanceof Consultant && filled($previousConsultant->email)) {
            Mail::to($previousConsultant->email)
                ->queue(new AppointmentConsultantUnassignedMail($appointment, $previousConsultant));
        }

        $appointment->loadMissing('consultant');
        $consultant = $appointment->consultant;

        if (! $consultant instanceof Consultant || blank($consultant->email)) {
            return;
        }

        // Recreate the event on the new consultant's calendar with a fresh meeting link.
        if (blank($appointment->google_event_id)) {
            try {
                dispatch_sync(new CreateAppointmentCalendarEventJob($appointment));
            } catch (Throwable) {
                Notification::make()
                    ->title(__('panel-admin::resources.appointments.actions.calendar_event_failed'))
                    ->warning()
                    ->send();
            }
        }

        // Notify the new consultant, mirroring the confirmation flow.
        Mail::to($consultant->email)
            ->queue(new AppointmentScheduledMail($appointment->refresh()));
    }
}
