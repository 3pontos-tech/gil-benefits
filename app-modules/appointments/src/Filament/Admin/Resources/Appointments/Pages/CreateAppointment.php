<?php

namespace TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\AppointmentResource;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
}
