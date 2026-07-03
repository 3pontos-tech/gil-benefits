<?php

namespace TresPontosTech\PanelAdmin\Filament\Pages;

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
use Livewire\Attributes\Url;
use TresPontosTech\Company\Enums\DepartmentCategory;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\AppointmentsByCategory;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\AppointmentsByDepartmentCategoryChart;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\AppointmentVolume;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\GlobalAppointmentStatsWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\KPIsOverview;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\RankingsWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\SupportTicketsByCategory;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\SupportTicketsBySector;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\SupportTicketsByStatus;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\SupportTicketStatsWidget;

class Metrics extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static string|null|\UnitEnum $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static string $routePath = 'metrics';

    protected static ?string $title = null;

    /** Active tab key — synced with ?tab= in the URL. */
    #[Url(as: 'tab')]
    public string $activeTab = 'consultants';

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
            Select::make('departmentCategory')
                ->label(__('panel-admin::resources.pages.metrics.filter_department_category'))
                ->placeholder(__('panel-admin::resources.pages.metrics.filter_department_category_placeholder'))
                ->options(DepartmentCategory::class)
                ->searchable()
                ->native(false)
                ->visible(fn (): bool => $this->activeTab === 'appointments'),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFiltersFormContentComponent(),
            Tabs::make()
                ->livewireProperty('activeTab')
                ->tabs([
                    'consultants' => Tab::make(__('panel-admin::resources.pages.metrics.tab_consultants'))
                        ->schema([
                            Grid::make(['xl' => 2])
                                ->schema($this->getWidgetsSchemaComponents([
                                    KPIsOverview::class,
                                    AppointmentVolume::class,
                                    AppointmentsByCategory::class,
                                    RankingsWidget::class,
                                ])),
                        ]),
                    'appointments' => Tab::make(__('panel-admin::resources.pages.metrics.tab_appointments'))
                        ->schema([
                            Grid::make(['xl' => 2])
                                ->schema($this->getWidgetsSchemaComponents([
                                    GlobalAppointmentStatsWidget::class,
                                    AppointmentsByDepartmentCategoryChart::class,
                                ])),
                        ]),
                    'support' => Tab::make(__('panel-admin::resources.pages.metrics.tab_support'))
                        ->schema([
                            Grid::make(['xl' => 2])
                                ->schema($this->getWidgetsSchemaComponents([
                                    SupportTicketStatsWidget::class,
                                    SupportTicketsByStatus::class,
                                    SupportTicketsBySector::class,
                                    SupportTicketsByCategory::class,
                                ])),
                        ]),
                ]),
        ]);
    }
}
