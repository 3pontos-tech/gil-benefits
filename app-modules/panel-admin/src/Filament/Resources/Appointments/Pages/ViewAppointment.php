<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
