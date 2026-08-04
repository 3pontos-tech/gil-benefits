<?php

namespace TresPontosTech\PanelApp\Filament\Resources\Appointments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelApp\Filament\Concerns\ConfirmsAppointmentCancellation;
use TresPontosTech\PanelApp\Filament\Contracts\ShowsCancelledConfirmation;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\AppointmentResource;

class ListAppointments extends ListRecords implements ShowsCancelledConfirmation
{
    use ConfirmsAppointmentCancellation;

    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->disabled(! auth()->user()->canCreateAppointment()),
        ];
    }
}
