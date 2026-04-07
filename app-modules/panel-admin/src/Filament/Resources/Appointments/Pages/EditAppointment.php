<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;

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
    }
}
