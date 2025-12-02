<?php

namespace TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\MonitoringDashboardResource;

class EditMonitoringDashboard extends EditRecord
{
    protected static string $resource = MonitoringDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
