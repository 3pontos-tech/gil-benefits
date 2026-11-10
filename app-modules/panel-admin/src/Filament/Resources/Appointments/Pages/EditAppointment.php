<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages;

use Carbon\CarbonInterface;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Appointments\Actions\SyncAppointmentScheduleAction;
use TresPontosTech\Appointments\Enums\AppointmentChange;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentChangedNotification;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\AppointmentResource;

/**
 * @property-read Appointment $record
 */
class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected ?string $previousConsultantId = null;

    protected ?CarbonInterface $previousAppointmentAt = null;

    protected ?string $previousMeetingUrl = null;

    protected ?AppointmentStatus $previousStatus = null;

    protected ?Appointment $appointment = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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
        $this->previousMeetingUrl = $appointment->meeting_url;
        $this->previousStatus = $appointment->status;

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Appointment $appointment */
        $appointment = $this->record;

        try {
            $calendarSynced = resolve(SyncAppointmentScheduleAction::class)
                ->handle($appointment, $this->previousConsultantId, $this->previousAppointmentAt, AppointmentHistoryActor::Admin);
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

        $this->tellTheEmployee($appointment);

        $this->redirect(AppointmentResource::getUrl('view', ['record' => $this->record]));
    }

    /**
     * Avisa a pessoa quando a equipe mudou um encontro já confirmado (gil-benefits#349). Sai daqui,
     * e não da sincronização da agenda, porque confirmar e o reagendamento feito pela própria
     * pessoa também passam por ela.
     */
    private function tellTheEmployee(Appointment $appointment): void
    {
        if (! $this->previousStatus instanceof AppointmentStatus || ! $this->previousAppointmentAt instanceof CarbonInterface) {
            return;
        }

        $change = AppointmentChange::between(
            $this->previousStatus,
            $this->previousConsultantId,
            $this->previousAppointmentAt,
            $this->previousMeetingUrl,
            $appointment,
        );

        if ($change instanceof AppointmentChange) {
            $appointment->user->notify(new AppointmentChangedNotification($appointment, $change));
        }
    }
}
