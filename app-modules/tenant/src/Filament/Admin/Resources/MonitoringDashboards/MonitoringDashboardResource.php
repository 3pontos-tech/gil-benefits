<?php

namespace TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards;

use App\Models\MonitoringDashboard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Pages\CreateMonitoringDashboard;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Pages\EditMonitoringDashboard;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Pages\ListMonitoringDashboards;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Pages\ViewMonitoringDashboard;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Schemas\MonitoringDashboardForm;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Schemas\MonitoringDashboardInfolist;
use TresPontosTech\Tenant\Filament\Admin\Resources\MonitoringDashboards\Tables\MonitoringDashboardsTable;

class MonitoringDashboardResource extends Resource
{
    protected static ?string $model = MonitoringDashboard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MonitoringDashboardForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MonitoringDashboardInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonitoringDashboardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoringDashboards::route('/'),
            'create' => CreateMonitoringDashboard::route('/create'),
            'view' => ViewMonitoringDashboard::route('/{record}'),
            'edit' => EditMonitoringDashboard::route('/{record}/edit'),
        ];
    }
}
