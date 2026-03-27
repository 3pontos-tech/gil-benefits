<?php

namespace TresPontosTech\Admin\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Admin\Filament\Widgets\Metrics\AppointmentsByCategory;
use TresPontosTech\Admin\Filament\Widgets\Metrics\AppointmentVolume;
use TresPontosTech\Admin\Filament\Widgets\Metrics\KPIsOverview;
use TresPontosTech\Admin\Filament\Widgets\Metrics\RankingsWidget;

class Metrics extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static string|null|\UnitEnum $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static string $routePath = 'metrics';

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.reports');
    }

    public function getTitle(): string|Htmlable
    {
        return __('panel-admin::resources.pages.metrics.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.pages.metrics.navigation_label');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('startDate')
                ->label(__('panel-admin::resources.pages.metrics.filter_start_date'))
                ->default(now()->subDays(30)),
            DatePicker::make('endDate')
                ->label(__('panel-admin::resources.pages.metrics.filter_end_date'))
                ->default(now()),
        ]);
    }

    public function getColumns(): int|array
    {
        return [
            'xl' => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            KPIsOverview::class,
            AppointmentVolume::class,
            AppointmentsByCategory::class,
            RankingsWidget::class,
        ];
    }
}
