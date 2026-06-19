<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\AdoptionFunnelWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CategoryMixWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CreditKpisWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\DepartmentAdoptionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\SatisfactionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\SessionsTrendWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\StatusBreakdownWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\TopConsultantsWidget;

class CommandDashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?int $navigationSort = -2;

    /** Keep the panel home route name (filament.company.pages.dashboard) stable. */
    protected static ?string $slug = 'dashboard';

    public static function getNavigationLabel(): string
    {
        return __('panel-company::resources.pages.command_dashboard.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('panel-company::resources.pages.command_dashboard.subheading');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewMetrics')
                ->label(__('panel-company::resources.pages.command_dashboard.view_metrics'))
                ->icon(Heroicon::ChartBar)
                ->color('gray')
                ->url(fn (): string => Metrics::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)
                ->columnSpanFull()
                ->schema($this->getWidgetsSchemaComponents([
                    AdoptionFunnelWidget::class,
                    CreditKpisWidget::class,
                    SessionsTrendWidget::class,
                    SatisfactionWidget::class,
                    StatusBreakdownWidget::class,
                    DepartmentAdoptionWidget::class,
                    CategoryMixWidget::class,
                    TopConsultantsWidget::class,
                ])),
        ]);
    }
}
