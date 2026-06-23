<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\ViewRecord;
use TresPontosTech\PanelConsultant\Filament\Resources\Appointments\AppointmentResource;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;
}
