<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelConsultant\Filament\Resources\Appointments\AppointmentResource;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;
}
