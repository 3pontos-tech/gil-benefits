<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\AppointmentResource;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = AppointmentStatus::Pending;

        return $data;
    }

    /**
     * Book the appointment end-to-end when a consultant is assigned on creation:
     * block the consultant's agenda, move it to Active and create the Google
     * Calendar event (attendee + meeting link). Mirrors ViewAppointment's confirm action.
     */
    protected function afterCreate(): void
    {
        /** @var Appointment $appointment */
        $appointment = $this->record;

        if (blank($appointment->consultant_id)) {
            return;
        }

        try {
            resolve(AssignConsultantAction::class)->handle($appointment);
        } catch (SlotUnavailableException) {
            Notification::make()
                ->title(__('appointments::resources.appointments.exceptions.consultant_unavailable'))
                ->danger()
                ->send();

            return;
        }

        $appointment->refresh();
        $appointment->current_transition->handle(new TransitionData);

        $appointment->loadMissing('consultant');

        $consultant = $appointment->consultant;

        if (filled($consultant) && filled($consultant->email) && blank($appointment->google_event_id)) {
            try {
                dispatch_sync(new CreateAppointmentCalendarEventJob($appointment));
            } catch (Throwable) {
                Notification::make()
                    ->title(__('panel-admin::resources.appointments.actions.calendar_event_failed'))
                    ->warning()
                    ->send();
            }
        }

        $this->record->refresh();
    }
}
