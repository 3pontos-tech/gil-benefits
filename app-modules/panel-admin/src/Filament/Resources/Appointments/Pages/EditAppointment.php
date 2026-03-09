<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Models\Appointment;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var Appointment $appointment */
        $appointment = $this->record;

        if ($appointment->wasChanged('consultant_id')) {
            resolve(AssignConsultantAction::class)->handle($appointment);
        }
    }
}
