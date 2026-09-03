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
use TresPontosTech\PanelAdmin\Actions\Financial\GetExtraCredits;
use TresPontosTech\PanelAdmin\DTOs\Financial\ExtraCreditsRow;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Créditos utilizados por empresa e por origem (STORY-240).
 */
class ExtraCreditsTableWidget extends TableWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-admin::widgets.financial.extra_credits.heading'))
            ->description(__('panel-admin::widgets.financial.extra_credits.description'))
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search, int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                $sortColumn,
                $sortDirection,
                (int) $page,
                $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('panel-admin::widgets.financial.extra_credits.company'))
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('from_plan')
                    ->label(__('panel-admin::widgets.financial.extra_credits.from_plan'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('purchased')
                    ->label(__('panel-admin::widgets.financial.extra_credits.purchased'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('granted')
                    ->label(__('panel-admin::widgets.financial.extra_credits.granted'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state))
                    ->description(fn (array $record): string => (int) $record['granted'] > 0
                        ? __('panel-admin::widgets.financial.extra_credits.granted_free')
                        : ''),

                TextColumn::make('purchased_value_cents')
                    ->label(__('panel-admin::widgets.financial.extra_credits.value'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => MoneyCents::fromCents((int) $state)->format()),
            ])
            ->defaultSort('purchased_value_cents', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading(__('panel-admin::widgets.financial.extra_credits.empty_heading'))
            ->emptyStateDescription(__('panel-admin::widgets.financial.extra_credits.empty_description'));
    }

    /**
     * @return LengthAwarePaginatorContract<int, array<string, mixed>>
     */
    protected function paginatedRows(
        ?string $sortColumn,
        ?string $sortDirection,
        int $page,
        int|string $recordsPerPage,
    ): LengthAwarePaginatorContract {
        $column = $sortColumn ?? 'purchased_value_cents';
        $descending = ($sortDirection ?? 'desc') === 'desc';

        $rows = resolve(GetExtraCredits::class)
            ->handle($this->financialFilters())
            ->map(fn (ExtraCreditsRow $row): array => $row->toArray())
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
}
