<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentsByCategoryChart;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentsByDepartmentChart;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentStatsWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentVolumeChart;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\CreditStatsWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\CreditUsageTableWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementStatsWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\InsightsWidget;

class Metrics extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?int $navigationSort = 5;

    protected static string $routePath = 'metrics';

    public function getTitle(): string|Htmlable
    {
        return __('panel-company::resources.pages.metrics.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-company::resources.pages.metrics.navigation_label');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('month')
                ->label(__('panel-company::resources.pages.metrics.filter_month'))
                ->placeholder(__('panel-company::resources.pages.metrics.filter_month_placeholder'))
                ->options($this->monthOptions())
                ->native(false),
            DatePicker::make('startDate')
                ->label(__('panel-company::resources.pages.metrics.filter_start_date'))
                ->default(now()->subDays(30)),
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
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function monthOptions(): array
    {
        $options = [];

        for ($i = 0; $i < 12; ++$i) {
            $date = now()->subMonthsNoOverflow($i);
            $options[$date->format('Y-m')] = ucfirst($date->translatedFormat('M/Y'));
        }

        return $options;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFiltersFormContentComponent(),
            Tabs::make()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make(__('panel-company::resources.pages.metrics.tab_consultations'))
                        ->schema([
                            Grid::make(['xl' => 2])
                                ->schema($this->getWidgetsSchemaComponents([
                                    AppointmentStatsWidget::class,
                                    AppointmentVolumeChart::class,
                                    AppointmentsByCategoryChart::class,
                                    AppointmentsByDepartmentChart::class,
                                ])),
                        ]),
                    Tab::make(__('panel-company::resources.pages.metrics.tab_engagement'))
                        ->schema([
                            Grid::make(['xl' => 2])
                                ->schema($this->getWidgetsSchemaComponents([
                                    EngagementStatsWidget::class,
                                    InsightsWidget::class,
                                ])),
                        ]),
                    Tab::make(__('panel-company::resources.pages.metrics.tab_credits'))
                        ->schema([
                            Grid::make(['xl' => 2])
                                ->schema($this->getWidgetsSchemaComponents([
                                    CreditStatsWidget::class,
                                    CreditUsageTableWidget::class,
                                ])),
                        ]),
                ]),
        ]);
    }
}
