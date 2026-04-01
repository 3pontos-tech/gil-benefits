<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Admin\Filament\Widgets\AppointmentsStatsOverview;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentsStatsOverview::class,
        ];
    }
}
