<?php

namespace TresPontosTech\PanelApp\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelApp\Filament\Concerns\ConfirmsAppointmentCancellation;
use TresPontosTech\PanelApp\Filament\Concerns\SchedulesAppointments;
use TresPontosTech\PanelApp\Filament\Contracts\ShowsCancelledConfirmation;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\AppointmentResource;

class ListAppointments extends ListRecords implements ShowsCancelledConfirmation
{
    use ConfirmsAppointmentCancellation;
    use SchedulesAppointments;

    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return __('panel-app::resources.appointments.table.title');
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
