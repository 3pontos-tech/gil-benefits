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
use TresPontosTech\PanelAdmin\Actions\Financial\GetConsultingValue;
use TresPontosTech\PanelAdmin\DTOs\Financial\ConsultingValue;
use TresPontosTech\PanelAdmin\DTOs\Financial\ConsultingValueRow;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Valor das consultorias consumidas no mês, por empresa (STORY-239).
 *
 * Não há coluna de margem: a plataforma não sabe por quanto uma consultoria é
 * vendida. As duas colunas de dinheiro ficam lado a lado para quem lê fazer a
 * comparação que interessa — consumo contra mensalidade.
 */
class ConsultingValueWidget extends TableWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-admin::widgets.financial.consulting_value.heading'))
            ->description($this->describe($this->consultingValue()))
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search, int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                $sortColumn,
                $sortDirection,
                (int) $page,
                $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('panel-admin::widgets.financial.consulting_value.company'))
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('billable')
                    ->label(__('panel-admin::widgets.financial.consulting_value.billable'))
                    ->alignEnd()
                    ->sortable()
                    ->weight('medium')
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state))
                    ->description(fn (array $record): string => $this->outcomes($record)),

                TextColumn::make('value_cents')
                    ->label(__('panel-admin::widgets.financial.consulting_value.value'))
                    ->alignEnd()
                    ->sortable()
                    ->badge(fn (mixed $state): bool => $state === null)
                    ->color(fn (mixed $state): string => $state === null ? 'warning' : 'gray')
                    ->formatStateUsing(fn (mixed $state): string => $state === null
                        ? __('panel-admin::widgets.financial.consulting_value.not_priced')
                        : MoneyCents::fromCents((int) $state)->format()),

                TextColumn::make('monthly_value_cents')
                    ->label(__('panel-admin::widgets.financial.consulting_value.monthly'))
                    ->alignEnd()
                    ->sortable()
                    ->badge(fn (mixed $state): bool => $state === null)
                    ->color(fn (mixed $state): string => $state === null ? 'warning' : 'gray')
                    ->formatStateUsing(fn (mixed $state): string => $state === null
                        ? __('panel-admin::widgets.financial.consulting_value.no_monthly')
                        : MoneyCents::fromCents((int) $state)->format())
                    ->tooltip(fn (mixed $state): ?string => $this->monthlyTooltip($state)),
            ])
            ->defaultSort('billable', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading(__('panel-admin::widgets.financial.consulting_value.empty_heading'))
            ->emptyStateDescription(__('panel-admin::widgets.financial.consulting_value.empty_description'));
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
        $column = $sortColumn ?? 'billable';
        $descending = ($sortDirection ?? 'desc') === 'desc';

        $rows = $this->consultingValue()->rows
            ->map(fn (ConsultingValueRow $row): array => $row->toArray())
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
     * O subtítulo carrega o total e, quando houver, quantas empresas ficaram sem
     * o outro lado da comparação.
     */
    private function describe(ConsultingValue $consultingValue): string
    {
        if (! $consultingValue->isConfigured()) {
            return __('panel-admin::widgets.financial.consulting_value.not_configured');
        }

        $base = __('panel-admin::widgets.financial.consulting_value.description', [
            'total' => MoneyCents::fromCents((int) $consultingValue->totalCents())->format(),
            'count' => EngagementNumber::integer($consultingValue->billableAppointments()),
        ]);

        $missing = $consultingValue->withoutMonthlyValue()->count();

        if ($missing < 1) {
            return $base;
        }

        return $base . ' · ' . trans_choice(
            'panel-admin::widgets.financial.consulting_value.missing_monthly',
            $missing,
            ['total' => EngagementNumber::integer($missing)],
        );
    }

    /**
     * Sem mensalidade cadastrada não há com o que comparar o consumo, e o
     * tooltip é onde isso fica dito para quem usa a tela.
     */
    private function monthlyTooltip(mixed $state): ?string
    {
        if ($state !== null) {
            return null;
        }

        return __('panel-admin::widgets.financial.consulting_value.no_monthly_hint');
    }

    /**
     * Os desfechos ficam sob a contagem, e não em três colunas: eles explicam a
     * base do cálculo, e não são número que alguém compara linha a linha.
     *
     * @param  array<string, mixed>  $record
     */
    private function outcomes(array $record): string
    {
        return __('panel-admin::widgets.financial.consulting_value.outcomes', [
            'completed' => EngagementNumber::integer((int) ($record['completed'] ?? 0)),
            'cancelled' => EngagementNumber::integer((int) ($record['cancelled_late'] ?? 0)),
            'no_show' => EngagementNumber::integer((int) ($record['no_show'] ?? 0)),
        ]);
    }

    private function consultingValue(): ConsultingValue
    {
        return resolve(GetConsultingValue::class)->handle($this->financialFilters());
    }
}
