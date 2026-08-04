<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Engagement;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetEngagementFunnel;
use TresPontosTech\PanelAdmin\DTOs\EngagementFunnelRow;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\Concerns\HasEngagementFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;
use TresPontosTech\PanelAdmin\Support\EngagementThresholds;

class EngagementFunnelTableWidget extends TableWidget
{
    use HasEngagementFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-admin::widgets.engagement.funnel.heading'))
            ->description(__('panel-admin::widgets.engagement.funnel.description'))
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search, int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                sortColumn: $sortColumn,
                sortDirection: $sortDirection,
                search: $search,
                page: (int) $page,
                recordsPerPage: $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('panel-admin::widgets.engagement.funnel.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(fn (array $record): ?Heroicon => $this->isCritical($record['completion_rate'])
                        ? Heroicon::ExclamationTriangle
                        : null)
                    ->iconColor('danger')
                    ->tooltip(fn (array $record): ?string => $this->criticalTooltip($record['completion_rate'])),

                TextColumn::make('seats')
                    ->label(__('panel-admin::widgets.engagement.funnel.seats'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('registered')
                    ->label(__('panel-admin::widgets.engagement.funnel.registered'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state))
                    ->description(fn (array $record): string => $this->rateDescription(
                        $record['registration_rate'],
                        'panel-admin::widgets.engagement.funnel.registration_rate_description',
                    )),

                TextColumn::make('with_appointment')
                    ->label(__('panel-admin::widgets.engagement.funnel.with_appointment'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state))
                    ->description(fn (array $record): string => $this->rateDescription(
                        $record['scheduling_rate'],
                        'panel-admin::widgets.engagement.funnel.scheduling_rate_description',
                    )),

                TextColumn::make('with_completed')
                    ->label(__('panel-admin::widgets.engagement.funnel.with_completed'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('completion_rate')
                    ->label(__('panel-admin::widgets.engagement.funnel.completion_rate'))
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::percent(
                        $state === null ? null : (float) $state,
                    ))
                    ->color(fn (mixed $state): string => $this->completionRateColor(
                        $state === null ? null : (float) $state,
                    )),

                TextColumn::make('with_recurrence')
                    ->label(__('panel-admin::widgets.engagement.funnel.with_recurrence'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state))
                    ->description(fn (array $record): string => $this->rateDescription(
                        $record['recurrence_rate'],
                        'panel-admin::widgets.engagement.funnel.recurrence_rate_description',
                    )),
            ])
            ->defaultSort('completion_rate')
            ->paginationMode(PaginationMode::Default)
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading(__('panel-admin::widgets.engagement.funnel.empty_heading'))
            ->emptyStateDescription(__('panel-admin::widgets.engagement.funnel.empty_description'));
    }

    /**
     * The current page of the funnel.
     *
     * @return LengthAwarePaginatorContract<string, array<string, mixed>>
     */
    protected function paginatedRows(
        ?string $sortColumn,
        ?string $sortDirection,
        ?string $search,
        int $page,
        int|string $recordsPerPage,
    ): LengthAwarePaginatorContract {
        $rows = $this->sortedRows($sortColumn, $sortDirection, $search);

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
     * The whole funnel, searched and sorted, keyed by company. Rows without a
     * rate always sink to the bottom so that companies with no activity in the
     * period do not pollute the top of the ranking.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function sortedRows(?string $sortColumn, ?string $sortDirection, ?string $search): array
    {
        $column = $sortColumn ?? 'completion_rate';
        $descending = $sortDirection === 'desc';

        $rows = resolve(GetEngagementFunnel::class)
            ->handle($this->engagementFilters())
            ->map(fn (EngagementFunnelRow $row): array => $row->toArray());

        if (filled($search)) {
            $rows = $rows->filter(
                fn (array $row): bool => Str::contains($row['company_name'], $search, ignoreCase: true),
            );
        }

        return $rows
            ->sortBy(
                fn (array $row): mixed => $row[$column] ?? ($descending ? -INF : INF),
                descending: $descending,
            )
            ->keyBy('company_id')
            ->all();
    }

    private function criticalTooltip(?float $rate): ?string
    {
        if (! $this->isCritical($rate)) {
            return null;
        }

        return __('panel-admin::widgets.engagement.funnel.critical_tooltip');
    }

    private function rateDescription(?float $rate, string $key): string
    {
        return __($key, ['rate' => EngagementNumber::percent($rate)]);
    }

    private function completionRateColor(?float $rate): string
    {
        return match (true) {
            $rate === null => 'gray',
            $this->isCritical($rate) => 'danger',
            default => 'success',
        };
    }

    private function isCritical(?float $rate): bool
    {
        return $rate !== null && $rate < EngagementThresholds::COMPANY_COMPLETION_RATE;
    }
}
