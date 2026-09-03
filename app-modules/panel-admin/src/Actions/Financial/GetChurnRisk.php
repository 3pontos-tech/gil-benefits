<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetEngagementFunnel;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;
use TresPontosTech\PanelAdmin\DTOs\EngagementFunnelRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\ChurnRiskReport;
use TresPontosTech\PanelAdmin\DTOs\Financial\ChurnRiskRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Enums\ChurnRiskLevel;
use TresPontosTech\PanelAdmin\Support\FinancialThresholds;

/**
 * Empresas em risco de churn: baixo uso somado a alto valor (STORY-235).
 *
 * A utilização não é recalculada aqui — vem do `GetEngagementFunnel`, o mesmo
 * que alimenta o relatório de engajamento e alimentará a STORY-241. Duas
 * definições de "utilização" no mesmo produto significariam o CS e o financeiro
 * discutindo qual tela está certa.
 */
final class GetChurnRisk
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'churn_risk';

    public function __construct(
        private readonly GetEngagementFunnel $funnel,
        private readonly GetContractsTable $contracts,
    ) {}

    public function handle(FinancialFilters $filters, ?CarbonImmutable $now = null): ChurnRiskReport
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): ChurnRiskReport => $this->build($filters, $now ?? CarbonImmutable::now()),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    private function build(FinancialFilters $filters, CarbonImmutable $now): ChurnRiskReport
    {
        /** @var Collection<string, int> $values */
        $values = $this->contracts->handle($filters, $now)
            ->filter(fn (ContractRow $row): bool => $row->monthlyValue->isKnown())
            ->mapWithKeys(fn (ContractRow $row): array => [$row->companyId => (int) $row->monthlyValue->cents]);

        $totalCompanies = $this->contracts->handle($filters, $now)->count();
        $median = $this->median($values->values()->all());

        $rows = $this->funnel
            ->handle($this->engagementFiltersFrom($filters))
            ->filter(fn (EngagementFunnelRow $row): bool => $values->has($row->companyId))
            ->map(fn (EngagementFunnelRow $row): ?ChurnRiskRow => $this->riskRowFor($row, $values, $median))
            ->filter()
            ->sortByDesc(fn (ChurnRiskRow $row): array => [$row->monthlyValueCents, $row->level->weight()])
            ->values();

        return new ChurnRiskReport(
            rows: $rows,
            medianValueCents: $median,
            companiesWithoutValue: $totalCompanies - $values->count(),
            companiesEvaluated: $values->count(),
        );
    }

    /**
     * Uma empresa entra na lista quando cumpre as duas condições da story ao
     * mesmo tempo. Só uma delas não basta: base pouco engajada que paga pouco é
     * problema de produto, e cliente caro que usa bem não é risco nenhum.
     *
     * @param  Collection<string, int>  $values
     */
    private function riskRowFor(EngagementFunnelRow $row, Collection $values, int $median): ?ChurnRiskRow
    {
        $usageRate = $row->usageRate();
        $valueCents = $values->get($row->companyId);

        if ($usageRate === null || $valueCents === null) {
            return null;
        }

        if ($usageRate >= FinancialThresholds::CHURN_USAGE_RATE || $valueCents <= $median) {
            return null;
        }

        return new ChurnRiskRow(
            companyId: $row->companyId,
            companyName: $row->companyName,
            usageRate: $usageRate,
            monthlyValueCents: $valueCents,
            registered: $row->registered,
            withCompletedAppointment: $row->withCompletedAppointment,
            level: ChurnRiskLevel::fromUsageRate($usageRate),
        );
    }

    /**
     * O funil trabalha com intervalo de datas; o cockpit, com mês fechado.
     * A conversão fica aqui para o resto do módulo continuar falando em mês.
     */
    private function engagementFiltersFrom(FinancialFilters $filters): EngagementFilters
    {
        return new EngagementFilters(
            start: $filters->period->start,
            end: $filters->period->end,
            companyIds: $filters->companyIds,
        );
    }

    /**
     * Mediana dos valores conhecidos.
     *
     * Mediana e não média de propósito: um único contrato grande puxaria a média
     * para cima e esconderia justamente os clientes de valor médio-alto que o CS
     * ainda consegue salvar.
     *
     * @param  array<int, int>  $values
     */
    private function median(array $values): int
    {
        if ($values === []) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? $values[$middle]
            : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }
}
