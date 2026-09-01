<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\DTOs\Financial\PaymentStatusRow;

/**
 * Pagamentos do mês por situação (STORY-236).
 *
 * A story pedia cinco status: aprovado, pendente, recusado, expirado e
 * estornado. Três não existem na origem — a Virtu não emite evento de cobrança
 * recusada (uma renovação que falha não gera cobrança nenhuma, só muda o status
 * da assinatura), não há conceito de expiração, e estorno chega mas ainda não é
 * tratado. A tela entrega os três que existem e diz isso ao usuário (D-04).
 */
final class GetPaymentTotals
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'payment_totals';

    public function __construct(private readonly GetContractsTable $contracts) {}

    /**
     * @return Collection<int, PaymentStatusRow>
     */
    public function handle(FinancialFilters $filters, ?CarbonImmutable $now = null): Collection
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): Collection => $this->build($filters, $now),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    /**
     * @return Collection<int, PaymentStatusRow>
     */
    private function build(FinancialFilters $filters, ?CarbonImmutable $now): Collection
    {
        $companies = $this->contracts->handle($filters, $now);
        $orders = $this->creditOrdersByStatus($filters);

        return collect([
            $this->row(
                CompanyFinancialStatusEnum::Active,
                $companies,
                (int) ($orders[CreditOrderStatusEnum::Paid->value]['count'] ?? 0),
                (int) ($orders[CreditOrderStatusEnum::Paid->value]['total'] ?? 0),
            ),
            $this->row(
                CompanyFinancialStatusEnum::Trial,
                $companies,
                (int) ($orders[CreditOrderStatusEnum::Pending->value]['count'] ?? 0),
                (int) ($orders[CreditOrderStatusEnum::Pending->value]['total'] ?? 0),
            ),
            $this->row(CompanyFinancialStatusEnum::Delinquent, $companies, 0, 0),
        ]);
    }

    /**
     * Uma linha do resumo: assinaturas naquele status somadas às compras de
     * crédito da natureza correspondente.
     *
     * Pendente reúne a assinatura ainda não confirmada e o pedido de crédito
     * aguardando pagamento — nos dois casos é dinheiro que ainda não entrou.
     *
     * @param  Collection<int, ContractRow>  $companies
     */
    private function row(
        CompanyFinancialStatusEnum $status,
        Collection $companies,
        int $orderCount,
        int $orderCents,
    ): PaymentStatusRow {
        $matching = $companies->filter(fn (ContractRow $row): bool => $row->status === $status);

        $subscriptionCents = (int) $matching
            ->filter(fn (ContractRow $row): bool => $row->monthlyValue->isKnown())
            ->sum(fn (ContractRow $row): int => (int) $row->monthlyValue->cents);

        return new PaymentStatusRow(
            status: $status->value,
            label: $status->getLabel(),
            color: match ($status) {
                CompanyFinancialStatusEnum::Active => 'success',
                CompanyFinancialStatusEnum::Trial => 'warning',
                default => 'danger',
            },
            subscriptions: $matching->count(),
            creditOrders: $orderCount,
            totalCents: $subscriptionCents + $orderCents,
        );
    }

    /**
     * Compras de crédito do mês, agrupadas pela situação do pedido.
     *
     * Pagas contam pela data do pagamento; pendentes, pela data em que foram
     * abertas — um pedido aberto em julho e ainda pendente em agosto continua
     * sendo dinheiro que não entrou em julho.
     *
     * @return array<string, array{count: int, total: int}>
     */
    private function creditOrdersByStatus(FinancialFilters $filters): array
    {
        $paid = CreditOrder::query()
            ->where('status', CreditOrderStatusEnum::Paid)
            ->whereBetween('paid_at', [$filters->period->start, $filters->period->end])
            ->when($filters->isFilteredByCompany(), fn ($query) => $query->whereIn('company_id', $filters->companyIds))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_cents), 0) as total')
            ->first();

        $pending = CreditOrder::query()
            ->where('status', CreditOrderStatusEnum::Pending)
            ->whereBetween('created_at', [$filters->period->start, $filters->period->end])
            ->when($filters->isFilteredByCompany(), fn ($query) => $query->whereIn('company_id', $filters->companyIds))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_cents), 0) as total')
            ->first();

        return [
            CreditOrderStatusEnum::Paid->value => [
                'count' => (int) ($paid->count ?? 0),
                'total' => (int) ($paid->total ?? 0),
            ],
            CreditOrderStatusEnum::Pending->value => [
                'count' => (int) ($pending->count ?? 0),
                'total' => (int) ($pending->total ?? 0),
            ],
        ];
    }
}
