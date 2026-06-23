<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\AppointmentResource;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
}
