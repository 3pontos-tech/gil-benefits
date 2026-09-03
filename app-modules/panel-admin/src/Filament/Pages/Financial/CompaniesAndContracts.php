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
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\ExportFinancialCsv;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Clusters\Financial\FinancialCluster;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ChurnRiskWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\CompanyStatusWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ContractsTableWidget;

/**
 * Módulo "Empresas e Contratos" do cockpit financeiro (STORY-233 a 235).
 *
 * Molde: a página `Engagement`, que já é o padrão de relatório do painel Admin.
 */
class CompaniesAndContracts extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $cluster = FinancialCluster::class;

    protected static string $routePath = 'companies-and-contracts';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = null;

    /**
     * Status escolhido ao clicar num card, sincronizado com a URL.
     *
     * A listagem que consome este filtro chega na STORY-234; o parâmetro nasce
     * aqui para que o cenário "Navegação por status" já funcione de ponta a
     * ponta assim que a tabela existir, sem retrabalho no widget.
     */
    #[Url(as: 'status')]
    public ?string $statusFilter = null;

    /** Janela de renovação em dias, vinda do card de renovação próxima. */
    #[Url(as: 'renewing')]
    public ?int $renewingWithinDays = null;

    /**
     * Uma porta só para todo o cockpit: a página delega ao cluster em vez de
     * repetir a regra, para que acesso direto pela URL não escape do gate.
     */
    public static function canAccess(): bool
    {
        return FinancialCluster::canAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return __('panel-admin::resources.pages.financial_companies.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('panel-admin::resources.pages.financial_companies.subheading');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.pages.financial_companies.navigation_label');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('month')
                        ->label(__('panel-admin::resources.pages.financial_companies.filter_month'))
                        ->options($this->monthOptions())
                        ->default(now()->format('Y-m'))
                        ->native(false),
                    Select::make('companies')
                        ->label(__('panel-admin::resources.pages.financial_companies.filter_companies'))
                        ->placeholder(__('panel-admin::resources.pages.financial_companies.filter_companies_placeholder'))
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

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFiltersFormContentComponent(),
            Grid::make(1)->schema($this->getWidgetsSchemaComponents([
                CompanyStatusWidget::class,
                ContractsTableWidget::class,
                ChurnRiskWidget::class,
            ])),
        ]);
    }

    /**
     * Repassa aos widgets o que veio da URL, além dos filtros do formulário.
     *
     * É por aqui que o clique num card da STORY-233 chega na listagem: o card
     * navega com `?status=`, a página lê no `#[Url]` e entrega ao widget de
     * tabela, que filtra as linhas já calculadas.
     *
     * @return array<string, mixed>
     */
    public function getWidgetData(): array
    {
        return [
            ...parent::getWidgetData(),
            'statusFilter' => $this->statusFilter,
            'renewingWithinDays' => $this->renewingWithinDays,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('panel-admin::resources.pages.financial_companies.export_csv'))
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => resolve(ExportFinancialCsv::class)->handle(
                    FinancialFilters::fromPageFilters($this->filters),
                    $this->exportableRows(),
                )),

            Action::make('clearStatusFilter')
                ->label(__('panel-admin::resources.pages.financial_companies.clear_status'))
                ->icon(Heroicon::XMark)
                ->color('gray')
                ->link()
                ->visible(fn (): bool => $this->statusFilter !== null || $this->renewingWithinDays !== null)
                ->action(function (): void {
                    $this->statusFilter = null;
                    $this->renewingWithinDays = null;
                }),
        ];
    }

    /**
     * As linhas que o CSV leva: exatamente o que está na tela, com os mesmos
     * filtros aplicados, como o cenário "Exportação da listagem" exige.
     *
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(): array
    {
        $widget = new ContractsTableWidget;
        $widget->pageFilters = $this->filters;
        $widget->statusFilter = $this->statusFilter;
        $widget->renewingWithinDays = $this->renewingWithinDays;

        return $widget->visibleRows();
    }

    /**
     * Rótulo do status ativo, para a tela dizer o que está filtrando.
     */
    public function activeStatus(): ?CompanyFinancialStatusEnum
    {
        return $this->statusFilter === null
            ? null
            : CompanyFinancialStatusEnum::tryFrom($this->statusFilter);
    }

    /**
     * Últimos 12 meses, do mais recente para o mais antigo.
     *
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
