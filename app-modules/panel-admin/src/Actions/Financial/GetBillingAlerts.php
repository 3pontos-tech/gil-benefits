<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\BillingAlert;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Alertas de cobrança (STORY-237).
 *
 * Os três alertas da story, com o terceiro adaptado: recusa recorrente não
 * existe na origem, então vira inadimplência (D-04).
 */
final class GetBillingAlerts
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'billing_alerts';

    private const int DUE_SOON_DAYS = 7;

    private const int RECENT_CANCELLATION_HOURS = 24;

    public function __construct(private readonly GetContractsTable $contracts) {}

    /**
     * @return Collection<int, BillingAlert>
     */
    public function handle(FinancialFilters $filters, ?CarbonImmutable $now = null): Collection
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): Collection => $this->build($filters, $now ?? CarbonImmutable::now()),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    /**
     * @return Collection<int, BillingAlert>
     */
    private function build(FinancialFilters $filters, CarbonImmutable $now): Collection
    {
        $companies = $this->contracts->handle($filters, $now);

        return collect([
            $this->dueSoon($companies, $now),
            $this->recentlyCancelled($companies, $now),
            $this->delinquent($companies),
        ])->reject(fn (BillingAlert $alert): bool => $alert->isEmpty())->values();
    }

    /**
     * Cobranças vencendo nos próximos dias.
     *
     * A data é estimada pelo ciclo mensal (D-05) — o alerta carrega essa marca
     * para a tela poder dizer que é projeção, não uma data vinda do gateway.
     *
     * @param  Collection<int, ContractRow>  $companies
     */
    private function dueSoon(Collection $companies, CarbonImmutable $now): BillingAlert
    {
        $limit = $now->addDays(self::DUE_SOON_DAYS);

        $matching = $companies->filter(fn (ContractRow $row): bool => $row->nextChargeAt instanceof CarbonImmutable
            && $row->nextChargeAt->betweenIncluded($now, $limit))->values();

        return new BillingAlert(
            key: 'due_soon',
            severity: 'warning',
            companies: $matching,
            totalCents: $this->sum($matching),
            isEstimatedDate: true,
        );
    }

    /**
     * Assinaturas canceladas nas últimas horas.
     *
     * O corte usa `ends_at`, que os handlers de gateway carimbam no momento do
     * cancelamento — é o registro mais próximo de "quando perdemos o cliente"
     * que existe hoje.
     *
     * @param  Collection<int, ContractRow>  $companies
     */
    private function recentlyCancelled(Collection $companies, CarbonImmutable $now): BillingAlert
    {
        $recentIds = Subscription::query()
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$now->subHours(self::RECENT_CANCELLATION_HOURS), $now])
            ->pluck('subscriptionable_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        $matching = $companies->filter(
            fn (ContractRow $row): bool => in_array($row->companyId, $recentIds, strict: true),
        )->values();

        return new BillingAlert(
            key: 'recently_cancelled',
            severity: 'danger',
            companies: $matching,
            totalCents: $this->sum($matching),
        );
    }

    /**
     * Empresas inadimplentes.
     *
     * Substitui o alerta de "duas cobranças recusadas consecutivas" da story: a
     * Virtu não emite cobrança recusada, então a falha de renovação chega como
     * mudança de status da assinatura.
     *
     * @param  Collection<int, ContractRow>  $companies
     */
    private function delinquent(Collection $companies): BillingAlert
    {
        $matching = $companies->filter(
            fn (ContractRow $row): bool => $row->status === CompanyFinancialStatusEnum::Delinquent,
        )->values();

        return new BillingAlert(
            key: 'delinquent',
            severity: 'danger',
            companies: $matching,
            totalCents: $this->sum($matching),
        );
    }

    /**
     * @param  Collection<int, ContractRow>  $companies
     */
    private function sum(Collection $companies): int
    {
        return (int) $companies
            ->filter(fn (ContractRow $row): bool => $row->monthlyValue->isKnown())
            ->sum(fn (ContractRow $row): int => (int) $row->monthlyValue->cents);
    }
}
