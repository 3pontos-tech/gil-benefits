<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Pages\Financial;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueKpis;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Clusters\Financial\FinancialCluster;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\RevenueByPlanChartWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\RevenueKpisWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\RevenueRankingTableWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\RevenueSeriesChartWidget;

/**
 * Módulo "Receita e Faturamento" do cockpit financeiro (STORY-230 a 232).
 */
class RevenueDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $cluster = FinancialCluster::class;

    protected static string $routePath = 'revenue';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = null;

    public static function canAccess(): bool
    {
        return FinancialCluster::canAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return __('panel-admin::resources.pages.financial_revenue.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('panel-admin::resources.pages.financial_revenue.subheading');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.pages.financial_revenue.navigation_label');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('month')
                        ->label(__('panel-admin::resources.pages.financial_revenue.filter_month'))
                        ->options($this->monthOptions())
                        ->default(now()->format('Y-m'))
                        ->native(false),
                    Select::make('companies')
                        ->label(__('panel-admin::resources.pages.financial_revenue.filter_companies'))
                        ->placeholder(__('panel-admin::resources.pages.financial_revenue.filter_companies_placeholder'))
                        ->options(fn (): array => Company::query()
                            ->withoutDefault()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->native(false),
                ]),
        ]);
    }

    /**
     * Recalcular na hora (D-12).
     *
     * Sem este botão, quem lança algo e vai conferir espera cinco minutos
     * achando que o painel travou.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate')
                ->label(__('panel-admin::resources.pages.financial_revenue.recalculate'))
                ->icon(Heroicon::ArrowPath)
                ->color('gray')
                ->action(function (): void {
                    resolve(GetRevenueKpis::class)->forget(FinancialFilters::fromPageFilters($this->filters));
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFiltersFormContentComponent(),
            Grid::make(1)->schema($this->getWidgetsSchemaComponents([
                RevenueKpisWidget::class,
                RevenueSeriesChartWidget::class,
            ])),
            Grid::make(2)->schema($this->getWidgetsSchemaComponents([
                RevenueByPlanChartWidget::class,
                RevenueRankingTableWidget::class,
            ])),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function monthOptions(): array
    {
        $options = [];

        foreach (range(0, 11) as $offset) {
            $month = now()->toImmutable()->subMonthsNoOverflow($offset);
            $options[$month->format('Y-m')] = ucfirst($month->translatedFormat('F/Y'));
        }

        return $options;
    }
}
