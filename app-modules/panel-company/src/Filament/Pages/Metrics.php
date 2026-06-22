<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\AdoptionFunnelWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CategoryMixWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\DepartmentAdoptionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\SatisfactionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\StatusBreakdownWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\TopConsultantsWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CompanyCreditStatsWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentStatsTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentVolumeChart;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\CreditFlowTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\CreditUsageTableWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\DepartmentVolumeChart;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementInsightsTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\NeverUsedTileWidget;

class Metrics extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?int $navigationSort = 5;

    protected static string $routePath = 'metrics';

    public static function getNavigationLabel(): string
    {
        return __('panel-company::resources.pages.metrics.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(4)
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('startDate')
                        ->label(__('panel-company::resources.pages.metrics.filter_start_date'))
                        ->default(now()->startOfMonth()),
                    DatePicker::make('endDate')
                        ->label(__('panel-company::resources.pages.metrics.filter_end_date'))
                        ->default(now()),
                    Select::make('userId')
                        ->label(__('panel-company::resources.pages.metrics.filter_user'))
                        ->placeholder(__('panel-company::resources.pages.metrics.filter_user_placeholder'))
                        ->options(fn (): array => Filament::getTenant()
                            ->employees()
                            ->orderBy('users.name')
                            ->pluck('users.name', 'users.id')
                            ->toArray()
                        )
                        ->searchable()
                        ->native(false),
                    Select::make('departmentId')
                        ->label(__('panel-company::resources.pages.metrics.filter_department'))
                        ->placeholder(__('panel-company::resources.pages.metrics.filter_department_placeholder'))
                        ->options(fn (): array => Filament::getTenant()
                            ->departments()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->searchable()
                        ->native(false),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFiltersFormContentComponent(),
            Tabs::make()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make(__('panel-company::resources.pages.metrics.tab_credits'))
                        ->schema([
                            $this->panoramaSection([
                                CompanyCreditStatsWidget::class,
                            ]),
                            $this->periodSection([
                                CreditFlowTilesWidget::class,
                                CreditUsageTableWidget::class,
                            ]),
                        ]),
                    Tab::make(__('panel-company::resources.pages.metrics.tab_sessions'))
                        ->schema([
                            Grid::make(12)->schema($this->getWidgetsSchemaComponents([
                                AppointmentStatsTilesWidget::class,
                                StatusBreakdownWidget::class,
                                CategoryMixWidget::class,
                                DepartmentVolumeChart::class,
                                AppointmentVolumeChart::class,
                            ])),
                        ]),
                    Tab::make(__('panel-company::resources.pages.metrics.tab_adoption'))
                        ->schema([
                            $this->panoramaSection([
                                AdoptionFunnelWidget::class,
                            ], 4),
                            $this->periodSection([
                                DepartmentAdoptionWidget::class,
                                NeverUsedTileWidget::class,
                            ], 4),
                        ]),
                    Tab::make(__('panel-company::resources.pages.metrics.tab_engagement'))
                        ->schema([
                            Grid::make(10)->schema($this->getWidgetsSchemaComponents([
                                EngagementTilesWidget::class,
                                EngagementInsightsTilesWidget::class,
                            ])),
                        ]),
                    Tab::make(__('panel-company::resources.pages.metrics.tab_experience'))
                        ->schema([
                            Grid::make(8)->schema($this->getWidgetsSchemaComponents([
                                SatisfactionWidget::class,
                                TopConsultantsWidget::class,
                            ])),
                        ]),
                ]),
        ]);
    }

    /**
     * Snapshot widgets that ignore every filter (current company-wide state).
     *
     * @param  array<int, class-string>  $widgets
     */
    private function panoramaSection(array $widgets, int $columns = 12): Section
    {
        return Section::make(__('panel-company::resources.pages.metrics.scope_panorama_heading'))
            ->description(__('panel-company::resources.pages.metrics.scope_panorama_description'))
            ->icon(Heroicon::OutlinedBuildingOffice2)
            ->secondary()
            ->iconColor('violet')
            ->schema([
                Grid::make($columns)->schema($this->getWidgetsSchemaComponents($widgets)),
            ]);
    }

    /**
     * Widgets that react to the selected date range and people filters.
     *
     * @param  array<int, class-string>  $widgets
     */
    private function periodSection(array $widgets, int $columns = 12): Section
    {
        return Section::make(__('panel-company::resources.pages.metrics.scope_period_heading'))
            ->description(__('panel-company::resources.pages.metrics.scope_period_description'))
            ->icon(Heroicon::OutlinedCalendarDays)
            ->iconColor('primary')
            ->secondary()
            ->schema([
                Grid::make($columns)->schema($this->getWidgetsSchemaComponents($widgets)),
            ]);
    }
}
