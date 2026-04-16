<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\ViewRecord;
use TresPontosTech\Consultants\Filament\Resources\Appointments\AppointmentResource;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;
}
