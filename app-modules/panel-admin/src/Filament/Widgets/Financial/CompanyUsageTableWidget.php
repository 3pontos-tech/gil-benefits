<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use TresPontosTech\PanelAdmin\Actions\Financial\GetCompanyUsage;
use TresPontosTech\PanelAdmin\Actions\Financial\GetCompanyUserBreakdown;
use TresPontosTech\PanelAdmin\DTOs\Financial\CompanyUsageRow;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Cruzamento de utilização por empresa, com detalhe por colaborador (STORY-241).
 */
class CompanyUsageTableWidget extends TableWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    /** Acima desta fatia sem uso, a linha ganha destaque de atenção. */
    private const float NEVER_USED_ALERT = 50.0;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-admin::widgets.financial.usage.heading'))
            ->description(__('panel-admin::widgets.financial.usage.description'))
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search, int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                $sortColumn,
                $sortDirection,
                $search,
                (int) $page,
                $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('panel-admin::widgets.financial.usage.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(fn (array $record): ?Heroicon => $this->needsAttention($record)
                        ? Heroicon::ExclamationTriangle
                        : null)
                    ->iconColor('warning')
                    ->tooltip(fn (array $record): ?string => $this->attentionTooltip($record)),

                TextColumn::make('seats')
                    ->label(__('panel-admin::widgets.financial.usage.seats'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('registered')
                    ->label(__('panel-admin::widgets.financial.usage.registered'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('used_in_period')
                    ->label(__('panel-admin::widgets.financial.usage.used'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('never_used')
                    ->label(__('panel-admin::widgets.financial.usage.never_used'))
                    ->alignEnd()
                    ->sortable()
                    ->badge(fn (array $record): bool => $this->needsAttention($record))
                    ->color(fn (array $record): string => $this->needsAttention($record) ? 'warning' : 'gray')
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state))
                    ->description(fn (array $record): string => EngagementNumber::percent(
                        $record['never_used_rate'] === null ? null : (float) $record['never_used_rate'],
                    )),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label(__('panel-admin::widgets.financial.usage.detail'))
                    ->icon(Heroicon::OutlinedUsers)
                    ->modalHeading(fn (array $record): string => (string) $record['company_name'])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('panel-admin::widgets.financial.usage.detail_close'))
                    ->modalContent(fn (array $record): View => view(
                        'panel-admin::filament.financial.company-users',
                        [
                            'users' => resolve(GetCompanyUserBreakdown::class)->handle(
                                (string) $record['company_id'],
                                $this->financialFilters()->period,
                            ),
                        ],
                    )),
            ])
            ->defaultSort('never_used', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading(__('panel-admin::widgets.financial.usage.empty_heading'))
            ->emptyStateDescription(__('panel-admin::widgets.financial.usage.empty_description'));
    }

    /**
     * @return LengthAwarePaginatorContract<int, array<string, mixed>>
     */
    protected function paginatedRows(
        ?string $sortColumn,
        ?string $sortDirection,
        ?string $search,
        int $page,
        int|string $recordsPerPage,
    ): LengthAwarePaginatorContract {
        $column = $sortColumn ?? 'never_used';
        $descending = ($sortDirection ?? 'desc') === 'desc';

        $rows = resolve(GetCompanyUsage::class)
            ->handle($this->financialFilters())
            ->map(fn (CompanyUsageRow $row): array => $row->toArray())
            ->when(
                filled($search),
                fn ($collection) => $collection->filter(
                    fn (array $row): bool => str_contains(
                        mb_strtolower((string) $row['company_name']),
                        mb_strtolower((string) $search),
                    ),
                ),
            )
            ->sortBy(fn (array $row): mixed => $row[$column] ?? null, descending: $descending)
            ->values()
            ->all();

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
     * @param  array<string, mixed>  $record
     */
    private function needsAttention(array $record): bool
    {
        $rate = $record['never_used_rate'] ?? null;

        return $rate !== null && (float) $rate > self::NEVER_USED_ALERT;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function attentionTooltip(array $record): ?string
    {
        if (! $this->needsAttention($record)) {
            return null;
        }

        return __('panel-admin::widgets.financial.usage.attention_tooltip');
    }
}
