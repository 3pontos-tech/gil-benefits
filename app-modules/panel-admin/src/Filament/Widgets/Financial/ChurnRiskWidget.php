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
use TresPontosTech\PanelAdmin\Actions\Financial\GetChurnRisk;
use TresPontosTech\PanelAdmin\DTOs\Financial\ChurnRiskReport;
use TresPontosTech\PanelAdmin\DTOs\Financial\ChurnRiskRow;
use TresPontosTech\PanelAdmin\Enums\ChurnRiskLevel;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Empresas em risco de churn (STORY-235).
 *
 * Ordenadas por maior valor em risco, como a story pede: a lista existe para o
 * CS decidir a quem ligar primeiro.
 */
class ChurnRiskWidget extends TableWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $report = $this->report();

        return $table
            ->heading(__('panel-admin::widgets.financial.churn.heading'))
            ->description($this->describe($report))
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search, int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                page: (int) $page,
                recordsPerPage: $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('panel-admin::widgets.financial.churn.company'))
                    ->weight('medium'),

                TextColumn::make('level')
                    ->label(__('panel-admin::widgets.financial.churn.level'))
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $this->levelOf($state)?->getLabel() ?? '')
                    ->color(fn (mixed $state): string => $this->levelOf($state)?->getColor() ?? 'gray'),

                TextColumn::make('usage_rate')
                    ->label(__('panel-admin::widgets.financial.churn.usage'))
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::percent((float) $state))
                    ->description(fn (array $record): string => __('panel-admin::widgets.financial.churn.usage_detail', [
                        'used' => EngagementNumber::integer((int) $record['with_completed']),
                        'registered' => EngagementNumber::integer((int) $record['registered']),
                    ])),

                TextColumn::make('monthly_value_cents')
                    ->label(__('panel-admin::widgets.financial.churn.value_at_risk'))
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => MoneyCents::fromCents((int) $state)->format()),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading(__('panel-admin::widgets.financial.churn.empty_heading'))
            ->emptyStateDescription(__('panel-admin::widgets.financial.churn.empty_description'));
    }

    private function report(): ChurnRiskReport
    {
        return resolve(GetChurnRisk::class)->handle($this->financialFilters());
    }

    /**
     * @return LengthAwarePaginatorContract<int, array<string, mixed>>
     */
    protected function paginatedRows(int $page, int|string $recordsPerPage): LengthAwarePaginatorContract
    {
        $rows = $this->report()->rows->map(fn (ChurnRiskRow $row): array => $row->toArray())->all();

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
     * Diz o total em risco e, quando houver, quantas empresas ficaram fora da
     * análise por não ter valor cadastrado — uma lista curta sem essa ressalva
     * seria lida como base saudável.
     */
    private function describe(ChurnRiskReport $report): string
    {
        if ($report->companiesWithoutValue > 0) {
            return __('panel-admin::widgets.financial.churn.description_with_gaps', [
                'value' => MoneyCents::fromCents($report->valueAtRiskCents())->format(),
                'median' => MoneyCents::fromCents($report->medianValueCents)->format(),
                'without' => EngagementNumber::integer($report->companiesWithoutValue),
            ]);
        }

        return __('panel-admin::widgets.financial.churn.description', [
            'value' => MoneyCents::fromCents($report->valueAtRiskCents())->format(),
            'median' => MoneyCents::fromCents($report->medianValueCents)->format(),
        ]);
    }

    private function levelOf(mixed $state): ?ChurnRiskLevel
    {
        return is_string($state) ? ChurnRiskLevel::tryFrom($state) : null;
    }
}
