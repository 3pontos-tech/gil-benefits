<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use TresPontosTech\Billing\Core\Support\MoneyCents;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueBreakdown;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\RevenueBreakdown;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Ranking de empresas por receita, com o alerta de concentração (STORY-232).
 */
class RevenueRankingTableWidget extends TableWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $breakdown = $this->breakdown();

        return $table
            ->heading(__('panel-admin::widgets.financial.ranking.heading'))
            ->description($this->describe($breakdown))
            ->records(fn (int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                (int) $page,
                $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('panel-admin::widgets.financial.ranking.company'))
                    ->weight('medium'),

                TextColumn::make('plan_name')
                    ->label(__('panel-admin::widgets.financial.ranking.plan'))
                    ->placeholder(__('panel-admin::widgets.financial.contracts.no_plan')),

                TextColumn::make('monthly_value_cents')
                    ->label(__('panel-admin::widgets.financial.ranking.value'))
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => MoneyCents::fromCents((int) $state)->format())
                    ->description(fn (array $record): string => $this->shareOf($record)),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading(__('panel-admin::widgets.financial.ranking.empty_heading'))
            ->emptyStateDescription(__('panel-admin::widgets.financial.ranking.empty_description'));
    }

    /**
     * @return LengthAwarePaginatorContract<int, array<string, mixed>>
     */
    protected function paginatedRows(int $page, int|string $recordsPerPage): LengthAwarePaginatorContract
    {
        $rows = $this->breakdown()->ranking->map(fn (ContractRow $row): array => $row->toArray())->all();

        $total = count($rows);
        $currentPage = max($page, 1);
        $perPage = $recordsPerPage === 'all' ? max($total, 1) : (int) $recordsPerPage;

        return new LengthAwarePaginator(
            items: array_slice($rows, ($currentPage - 1) * $perPage, $perPage, preserve_keys: true),
            total: $total,
            perPage: $perPage,
            currentPage: $currentPage,
            options: [
                'pageName' => $this->getTablePaginationPageName(),
                'path' => Paginator::resolveCurrentPath(),
            ],
        );
    }

    /**
     * O alerta de concentração vive no subtítulo do ranking, e não numa seção
     * separada, porque é sobre a primeira linha da própria tabela — lê-se junto.
     */
    private function describe(RevenueBreakdown $breakdown): string
    {
        $top = $breakdown->topCompany();

        if ($top instanceof ContractRow && $breakdown->hasConcentrationAlert()) {
            return __('panel-admin::widgets.financial.ranking.concentration_alert', [
                'company' => $top->companyName,
                'rate' => EngagementNumber::percent($breakdown->concentrationRate()),
            ]);
        }

        return __('panel-admin::widgets.financial.ranking.description', [
            'total' => MoneyCents::fromCents($breakdown->totalCents)->format(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function shareOf(array $record): string
    {
        $total = $this->breakdown()->totalCents;

        if ($total < 1) {
            return '';
        }

        $share = round(((int) $record['monthly_value_cents'] / $total) * 100, 1);

        return __('panel-admin::widgets.financial.ranking.share', [
            'share' => EngagementNumber::percent($share),
        ]);
    }

    private function breakdown(): RevenueBreakdown
    {
        return resolve(GetRevenueBreakdown::class)->handle($this->financialFilters());
    }
}
