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
use TresPontosTech\PanelAdmin\Actions\Financial\GetPaymentTotals;
use TresPontosTech\PanelAdmin\DTOs\Financial\PaymentStatusRow;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Pagamentos do mês por situação (STORY-236).
 *
 * O subtítulo diz o que o gateway não reporta. Sem esse aviso, a ausência de
 * "recusado" seria lida como "ninguém teve cobrança recusada", que é uma
 * afirmação diferente e falsa.
 */
class PaymentTotalsWidget extends TableWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-admin::widgets.financial.payments.heading'))
            ->description(__('panel-admin::widgets.financial.payments.description'))
            ->records(fn (int|string $page, int|string $recordsPerPage): LengthAwarePaginatorContract => $this->paginatedRows(
                (int) $page,
                $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('label')
                    ->label(__('panel-admin::widgets.financial.payments.status'))
                    ->badge()
                    ->color(fn (array $record): string => (string) $record['color']),

                TextColumn::make('quantity')
                    ->label(__('panel-admin::widgets.financial.payments.quantity'))
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => EngagementNumber::integer((int) $state))
                    ->description(fn (array $record): string => __('panel-admin::widgets.financial.payments.breakdown', [
                        'subscriptions' => EngagementNumber::integer((int) $record['subscriptions']),
                        'orders' => EngagementNumber::integer((int) $record['credit_orders']),
                    ])),

                TextColumn::make('total_cents')
                    ->label(__('panel-admin::widgets.financial.payments.total'))
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => MoneyCents::fromCents((int) $state)->format()),
            ])
            ->paginated([10])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading(__('panel-admin::widgets.financial.payments.empty_heading'));
    }

    /**
     * @return LengthAwarePaginatorContract<int, array<string, mixed>>
     */
    protected function paginatedRows(int $page, int|string $recordsPerPage): LengthAwarePaginatorContract
    {
        $rows = resolve(GetPaymentTotals::class)
            ->handle($this->financialFilters())
            ->map(fn (PaymentStatusRow $row): array => $row->toArray())
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
