<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages;

use Carbon\CarbonInterface;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Appointments\Actions\SyncAppointmentScheduleAction;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\AppointmentResource;

/**
 * @property-read Appointment $record
 */
class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected ?string $previousConsultantId = null;

    protected ?CarbonInterface $previousAppointmentAt = null;

    protected ?Appointment $appointment = null;

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
        // Capture the consultant/time before the record is filled: by the time afterSave()
        // runs, getOriginal() has already synced to the newly saved values.
        /** @var Appointment $appointment */
        $appointment = $this->record;
        $this->previousConsultantId = $appointment->consultant_id;
        $this->previousAppointmentAt = $appointment->appointment_at;

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Appointment $appointment */
        $appointment = $this->record;

        try {
            $calendarSynced = resolve(SyncAppointmentScheduleAction::class)
                ->handle($appointment, $this->previousConsultantId, $this->previousAppointmentAt);
        } catch (SlotUnavailableException) {
            Notification::make()
                ->title(__('appointments::resources.appointments.exceptions.consultant_unavailable'))
                ->danger()
                ->send();

            $this->halt();

            return;
        }

        $appointment->refresh();

        if (blank($appointment->consultant_id)) {
            Notification::make()
                ->title(__('panel-admin::resources.appointments.notifications.consultant_removed'))
                ->warning()
                ->send();
        }

        if (! $calendarSynced) {
            Notification::make()
                ->title(__('panel-admin::resources.appointments.actions.calendar_sync_failed'))
                ->warning()
                ->send();
        }

        $this->redirect(AppointmentResource::getUrl('view', ['record' => $this->record]));
    }
}
