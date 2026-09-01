<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Support\CompanyStatusResolver;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\CompanyStatusTotals;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Contagem de empresas por status financeiro (STORY-233).
 *
 * Lane A do épico: derivado do estado atual, cacheado por 5 minutos. Nenhum
 * snapshot, nenhuma tabela nova.
 */
final class GetCompanyStatusTotals
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'company_status_totals';

    public function __construct(private readonly CompanyStatusResolver $resolver) {}

    public function handle(FinancialFilters $filters, ?CarbonImmutable $now = null): CompanyStatusTotals
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): CompanyStatusTotals => $this->build($filters, $now ?? CarbonImmutable::now()),
        );
    }

    /**
     * Descarta o bloco para o botão de recalcular da tela (D-12).
     */
    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    private function build(FinancialFilters $filters, CarbonImmutable $now): CompanyStatusTotals
    {
        $query = Company::query()->withoutDefault()->with('subscriptions');

        if ($filters->isFilteredByCompany()) {
            $query->whereIn('id', $filters->companyIds);
        }

        $byStatus = [];
        $renewingIn30 = 0;
        $renewingIn7 = 0;

        // `subscriptions` vem pré-carregada, então o resolver não consulta por
        // linha. A busca do contrato só acontece para empresa sem assinatura.
        foreach ($query->lazy() as $company) {
            $result = $this->resolver->resolve($company, $now);

            $byStatus[$result->status->value] = ($byStatus[$result->status->value] ?? 0) + 1;

            if ($result->renewsWithin(30, $now)) {
                ++$renewingIn30;
            }

            if ($result->renewsWithin(7, $now)) {
                ++$renewingIn7;
            }
        }

        return new CompanyStatusTotals(
            byStatus: $this->withEveryStatus($byStatus),
            renewingIn30Days: $renewingIn30,
            renewingIn7Days: $renewingIn7,
            generatedAt: $now,
        );
    }

    /**
     * Garante zero explícito para todo status.
     *
     * Card ausente e card zerado são coisas diferentes na tela: o primeiro
     * parece bug, o segundo é informação (STORY-233, card "Em Trial").
     *
     * @param  array<string, int>  $counted
     * @return array<string, int>
     */
    private function withEveryStatus(array $counted): array
    {
        $totals = [];

        foreach (CompanyFinancialStatusEnum::cases() as $status) {
            $totals[$status->value] = $counted[$status->value] ?? 0;
        }

        return $totals;
    }
}
