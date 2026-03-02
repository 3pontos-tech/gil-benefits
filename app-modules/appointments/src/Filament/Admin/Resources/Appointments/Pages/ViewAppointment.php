<?php

namespace TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\AppointmentResource;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
