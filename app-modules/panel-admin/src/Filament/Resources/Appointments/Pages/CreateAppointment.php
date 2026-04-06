<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
}
