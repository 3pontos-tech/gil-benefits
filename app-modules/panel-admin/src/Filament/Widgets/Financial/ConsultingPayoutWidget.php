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
use TresPontosTech\PanelAdmin\Actions\Financial\GetConsultingPayout;
use TresPontosTech\PanelAdmin\DTOs\Financial\ConsultingPayout;
use TresPontosTech\PanelAdmin\DTOs\Financial\PayoutRow;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Repasse aos consultores no mês (STORY-239).
 *
 * Não há coluna de margem: a plataforma não sabe quanto uma consultoria vale.
 * Ela sabe quanto foi repassado, e é isso que a tela afirma.
 */
class ConsultingPayoutWidget extends TableWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $payout = $this->payout();

        return $table
            ->heading(__('panel-admin::widgets.financial.payout.heading'))
            ->description($this->describe($payout))
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search, int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                $sortColumn,
                $sortDirection,
                (int) $page,
                $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('consultant_name')
                    ->label(__('panel-admin::widgets.financial.payout.consultant'))
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('completed')
                    ->label(__('panel-admin::widgets.financial.payout.completed'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('cancelled_late')
                    ->label(__('panel-admin::widgets.financial.payout.cancelled_late'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('no_show')
                    ->label(__('panel-admin::widgets.financial.payout.no_show'))
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state)),

                TextColumn::make('billable')
                    ->label(__('panel-admin::widgets.financial.payout.billable'))
                    ->alignEnd()
                    ->sortable()
                    ->weight('medium')
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state))
                    ->description(__('panel-admin::widgets.financial.payout.billable_hint')),

                TextColumn::make('payout_cents')
                    ->label(__('panel-admin::widgets.financial.payout.value'))
                    ->alignEnd()
                    ->sortable()
                    ->badge(fn (mixed $state): bool => $state === null)
                    ->color(fn (mixed $state): string => $state === null ? 'warning' : 'gray')
                    ->formatStateUsing(fn (mixed $state): string => $state === null
                        ? __('panel-admin::widgets.financial.payout.no_cost')
                        : MoneyCents::fromCents((int) $state)->format())
                    ->description(fn (array $record): string => $this->costHint($record)),
            ])
            ->defaultSort('payout_cents', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading(__('panel-admin::widgets.financial.payout.empty_heading'))
            ->emptyStateDescription(__('panel-admin::widgets.financial.payout.empty_description'));
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
        $column = $sortColumn ?? 'payout_cents';
        $descending = ($sortDirection ?? 'desc') === 'desc';

        $rows = $this->payout()->rows
            ->map(fn (PayoutRow $row): array => $row->toArray())
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
     * O subtítulo carrega o total e, quando houver, o aviso de que ele está
     * incompleto — um total silenciosamente parcial é pior do que nenhum.
     */
    private function describe(ConsultingPayout $payout): string
    {
        if (! $payout->isConfigured()) {
            return __('panel-admin::widgets.financial.payout.not_configured');
        }

        $base = __('panel-admin::widgets.financial.payout.description', [
            'total' => MoneyCents::fromCents($payout->totalCents())->format(),
            'count' => EngagementNumber::integer($payout->billableAppointments()),
        ]);

        $missing = $payout->withoutCost()->count();

        if ($missing < 1) {
            return $base;
        }

        return $base . ' · ' . trans_choice(
            'panel-admin::widgets.financial.payout.missing_cost',
            $missing,
            ['total' => EngagementNumber::integer($missing)],
        );
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function costHint(array $record): string
    {
        $cost = $record['cost_per_appointment_cents'] ?? null;

        if ($cost === null) {
            return __('panel-admin::widgets.financial.payout.no_cost_hint');
        }

        $key = ($record['uses_default_cost'] ?? false)
            ? 'panel-admin::widgets.financial.payout.cost_default'
            : 'panel-admin::widgets.financial.payout.cost_own';

        return __($key, ['amount' => MoneyCents::fromCents((int) $cost)->format()]);
    }

    private function payout(): ConsultingPayout
    {
        return resolve(GetConsultingPayout::class)->handle($this->financialFilters());
    }
}
