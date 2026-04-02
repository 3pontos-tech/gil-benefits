<?php

namespace TresPontosTech\Consultants\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Consultants\Filament\Resources\Appointments\AppointmentResource;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;
}
