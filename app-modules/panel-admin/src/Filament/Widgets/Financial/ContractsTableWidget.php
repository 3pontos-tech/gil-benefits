<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

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
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Support\MoneyCents;
use TresPontosTech\PanelAdmin\Actions\Financial\GetContractsTable;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;

/**
 * Listagem de empresas e contratos (STORY-234).
 *
 * Os dados são derivados, não uma tabela do banco, então busca, ordenação e
 * paginação correm em memória sobre as linhas já calculadas — mesmo padrão do
 * `EngagementFunnelTableWidget`.
 */
class ContractsTableWidget extends TableWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    /** Status vindo do clique num card, repassado pela página (STORY-233, cenário 2). */
    public ?string $statusFilter = null;

    /** Janela de renovação em dias, vinda do card de renovação próxima. */
    public ?int $renewingWithinDays = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-admin::widgets.financial.contracts.heading'))
            ->description($this->activeFilterDescription())
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search, int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                sortColumn: $sortColumn,
                sortDirection: $sortDirection,
                search: $search,
                page: (int) $page,
                recordsPerPage: $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('panel-admin::widgets.financial.contracts.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('plan_name')
                    ->label(__('panel-admin::widgets.financial.contracts.plan'))
                    ->sortable()
                    ->placeholder(__('panel-admin::widgets.financial.contracts.no_plan')),

                TextColumn::make('monthly_value_cents')
                    ->label(__('panel-admin::widgets.financial.contracts.monthly_value'))
                    ->alignEnd()
                    ->sortable()
                    // O aviso entra como estado, e não como formatação: com o
                    // estado nulo o Filament renderiza o placeholder e nunca
                    // chama o `formatStateUsing` — a célula saía vazia, e a
                    // empresa sem preço passava por empresa sem informação.
                    ->state(fn (array $record): string => $this->valueLabel($record))
                    ->badge(fn (array $record): bool => ! $this->hasValue($record))
                    ->color(fn (array $record): string => $this->hasValue($record) ? 'gray' : 'warning')
                    ->tooltip(fn (array $record): ?string => $this->valueTooltip($record)),

                TextColumn::make('next_charge_at')
                    ->label(__('panel-admin::widgets.financial.contracts.next_charge'))
                    ->sortable()
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->icon(fn (mixed $state): ?Heroicon => $state === null ? null : Heroicon::OutlinedClock)
                    ->tooltip(fn (mixed $state): ?string => $this->nextChargeTooltip($state)),

                TextColumn::make('status')
                    ->label(__('panel-admin::widgets.financial.contracts.status'))
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $this->statusOf($state)?->getLabel() ?? (string) $state)
                    ->color(fn (mixed $state): string => match ($this->statusOf($state)) {
                        CompanyFinancialStatusEnum::Active => 'success',
                        CompanyFinancialStatusEnum::Trial => 'info',
                        CompanyFinancialStatusEnum::Delinquent => 'warning',
                        CompanyFinancialStatusEnum::Cancelled => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('next_charge_at')
            ->paginationMode(PaginationMode::Default)
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading(__('panel-admin::widgets.financial.contracts.empty_heading'))
            ->emptyStateDescription(__('panel-admin::widgets.financial.contracts.empty_description'));
    }

    /**
     * As linhas que estão na tela, já filtradas e ordenadas — é isto que o CSV
     * exporta, atendendo o cenário "Exportação da listagem" da story.
     *
     * @return array<int, array<string, mixed>>
     */
    public function visibleRows(?string $search = null): array
    {
        return array_values($this->sortedRows(null, null, $search));
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
     * Linhas filtradas e ordenadas.
     *
     * A ordem inicial é por próxima cobrança ascendente, como a story pede, e
     * quem não tem data vai para o fim — uma empresa cancelada não deveria
     * abrir a lista de quem cobra primeiro.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function sortedRows(?string $sortColumn, ?string $sortDirection, ?string $search): array
    {
        $column = $sortColumn ?? 'next_charge_at';
        $descending = $sortDirection === 'desc';

        $rows = resolve(GetContractsTable::class)
            ->handle($this->financialFilters())
            ->map(fn (ContractRow $row): array => $row->toArray());

        if ($this->statusFilter !== null) {
            $rows = $rows->where('status', $this->statusFilter);
        }

        if ($this->renewingWithinDays !== null) {
            $limit = now()->toImmutable()->addDays($this->renewingWithinDays)->toDateString();
            $today = now()->toImmutable()->toDateString();

            $rows = $rows->filter(fn (array $row): bool => $row['next_charge_at'] !== null
                && $row['next_charge_at'] >= $today
                && $row['next_charge_at'] <= $limit);
        }

        if (filled($search)) {
            $rows = $rows->filter(
                fn (array $row): bool => Str::contains((string) $row['company_name'], $search, ignoreCase: true),
            );
        }

        return $rows
            ->sortBy(fn (array $row): mixed => $row[$column] ?? ($descending ? '' : 'zzzz'), descending: $descending)
            ->values()
            ->all();
    }

    /**
     * Explica o badge de valor ausente: a empresa paga, o sistema é que não sabe
     * quanto.
     */
    /**
     * @param  array<string, mixed>  $record
     */
    private function valueTooltip(array $record): ?string
    {
        if ($this->hasValue($record)) {
            return null;
        }

        return __('panel-admin::widgets.financial.contracts.value_unknown_tooltip');
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function valueLabel(array $record): string
    {
        if (! $this->hasValue($record)) {
            return __('panel-admin::widgets.financial.contracts.value_unknown');
        }

        return MoneyCents::fromCents((int) $record['monthly_value_cents'])->format();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function hasValue(array $record): bool
    {
        return ($record['monthly_value_cents'] ?? null) !== null;
    }

    /**
     * A data é projetada pelo ciclo, não informada pelo gateway (D-05), e o
     * tooltip é onde isso fica dito para quem usa a tela.
     */
    private function nextChargeTooltip(mixed $state): ?string
    {
        if ($state === null) {
            return null;
        }

        return __('panel-admin::widgets.financial.contracts.next_charge_estimated');
    }

    private function statusOf(mixed $state): ?CompanyFinancialStatusEnum
    {
        return is_string($state) ? CompanyFinancialStatusEnum::tryFrom($state) : null;
    }

    /**
     * Diz na tela o que está filtrando, para o clique num card não parecer que
     * a listagem simplesmente perdeu linhas.
     */
    private function activeFilterDescription(): string
    {
        $status = $this->statusOf($this->statusFilter);

        if ($status instanceof CompanyFinancialStatusEnum) {
            return __('panel-admin::widgets.financial.contracts.filtered_by_status', [
                'status' => $status->getLabel(),
            ]);
        }

        if ($this->renewingWithinDays !== null) {
            return __('panel-admin::widgets.financial.contracts.filtered_by_renewal', [
                'days' => $this->renewingWithinDays,
            ]);
        }

        return __('panel-admin::widgets.financial.contracts.description');
    }
}
