<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Support\CompanyStatusResolver;
use TresPontosTech\Billing\Core\Support\RevenueResolver;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Listagem de empresas e contratos (STORY-234).
 *
 * Lane A: nada é persistido. Valor e status saem dos resolvers, e a listagem
 * inteira é montada em duas consultas — empresas com assinaturas e planos
 * pré-carregados, e os contratos vigentes de todas elas de uma vez.
 */
final class GetContractsTable
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'contracts_table';

    public function __construct(
        private readonly RevenueResolver $revenue,
        private readonly CompanyStatusResolver $status,
    ) {}

    /**
     * @return Collection<int, ContractRow>
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
     * @return Collection<int, ContractRow>
     */
    private function build(FinancialFilters $filters, CarbonImmutable $now): Collection
    {
        $query = Company::query()
            ->withoutDefault()
            ->with(['subscriptions.plan'])
            ->orderBy('name');

        if ($filters->isFilteredByCompany()) {
            $query->whereIn('id', $filters->companyIds);
        }

        $companies = $query->get();

        $contracts = $this->contractsFor($companies->modelKeys(), $now);

        return $companies->map(function (Company $company) use ($contracts, $now): ContractRow {
            $billing = $this->status->resolve($company, $now);
            $contract = $contracts->get((string) $company->getKey());

            return new ContractRow(
                companyId: (string) $company->getKey(),
                companyName: $company->name,
                planName: $this->planNameFor($company, $contract),
                monthlyValue: $this->revenue->monthlyValueForCompany($company),
                nextChargeAt: $billing->nextChargeAt,
                status: $billing->status,
            );
        })->values();
    }

    /**
     * Contratos vigentes de todas as empresas numa consulta só.
     *
     * A regra de vigência vem do scope `activeOn` do próprio `CompanyPlan`, o
     * mesmo que `Company::activeContractualPlan()` usa — a listagem não pode
     * discordar do detalhe sobre qual contrato está valendo.
     *
     * @param  array<int, mixed>  $companyIds
     * @return Collection<string, CompanyPlan>
     */
    private function contractsFor(array $companyIds, CarbonImmutable $now): Collection
    {
        if ($companyIds === []) {
            return collect();
        }

        return CompanyPlan::query()
            ->whereIn('company_id', $companyIds)
            ->activeOn($now)
            ->with('plan')
            ->get()
            ->keyBy(fn (CompanyPlan $plan): string => (string) $plan->company_id);
    }

    /**
     * Nome do plano, com a assinatura tendo precedência sobre o contrato — a
     * mesma ordem que o `RevenueResolver` usa para o valor, para as duas colunas
     * nunca falarem de planos diferentes na mesma linha.
     */
    private function planNameFor(Company $company, ?CompanyPlan $contract): ?string
    {
        $subscriptionPlan = $company->subscriptions
            ->sortByDesc('created_at')
            ->first()?->plan?->name;

        return $subscriptionPlan ?? $contract?->plan?->name;
    }
}
