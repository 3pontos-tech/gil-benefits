<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
