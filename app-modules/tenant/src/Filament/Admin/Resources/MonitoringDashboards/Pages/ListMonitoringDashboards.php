<?php

namespace TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\MonitoringDashboardResource;

class ListMonitoringDashboards extends ListRecords
{
    protected static string $resource = MonitoringDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
