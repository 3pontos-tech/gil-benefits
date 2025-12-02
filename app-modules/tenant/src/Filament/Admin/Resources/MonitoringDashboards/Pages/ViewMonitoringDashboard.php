<?php

namespace TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\MonitoringDashboardResource;

class ViewMonitoringDashboard extends ViewRecord
{
    protected static string $resource = MonitoringDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
