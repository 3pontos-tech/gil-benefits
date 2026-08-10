<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Engagement;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetWeeklyEngagement;
use TresPontosTech\PanelAdmin\DTOs\EngagementWeek;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\Concerns\HasEngagementFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;
use TresPontosTech\PanelAdmin\Support\EngagementThresholds;

class EngagementWeeklyTableWidget extends TableWidget
{
    use HasEngagementFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-admin::widgets.engagement.weekly_table.heading'))
            ->description(__('panel-admin::widgets.engagement.weekly_table.description'))
            ->records(fn (int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedWeeks(
                page: (int) $page,
                recordsPerPage: $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('week')
                    ->label(__('panel-admin::widgets.engagement.weekly_table.week'))
                    ->weight('medium'),

                TextColumn::make('scheduled')
                    ->label(__('panel-admin::widgets.engagement.weekly_table.scheduled'))
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('completed')
                    ->label(__('panel-admin::widgets.engagement.weekly_table.completed'))
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('completion_rate')
                    ->label(__('panel-admin::widgets.engagement.weekly_table.completion_rate'))
                    ->alignEnd()
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::percent(
                        $state === null ? null : (float) $state,
                    ))
                    ->color(fn (mixed $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state < EngagementThresholds::WEEKLY_COMPLETION_RATE => 'danger',
                        default => 'success',
                    }),
            ])
            ->paginationMode(PaginationMode::Default)
            ->paginated([10, 25, 50, 'all'])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading(__('panel-admin::widgets.engagement.weekly_table.empty_heading'));
    }

    /**
     * The current page of the weekly series, oldest week first.
     *
     * @return LengthAwarePaginatorContract<string, array<string, mixed>>
     */
    protected function paginatedWeeks(int $page, int|string $recordsPerPage): LengthAwarePaginatorContract
    {
        $weeks = $this->weeks();

        $total = count($weeks);
        $currentPage = max($page, 1);
        $perPage = $recordsPerPage === 'all' ? max($total, 1) : (int) $recordsPerPage;

        return new LengthAwarePaginator(
            items: array_slice($weeks, ($currentPage - 1) * $perPage, $perPage, preserve_keys: true),
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
     * @return array<string, array<string, mixed>>
     */
    protected function weeks(): array
    {
        return resolve(GetWeeklyEngagement::class)
            ->handle($this->engagementFilters())
            ->map(fn (EngagementWeek $week): array => $week->toArray())
            ->keyBy('starts_at')
            ->all();
    }
}
