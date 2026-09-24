<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialPeriod;
use TresPontosTech\PanelAdmin\DTOs\Financial\MonthlyRevenue;
use TresPontosTech\PanelAdmin\DTOs\Financial\RevenueKpis;
use TresPontosTech\PanelAdmin\Support\RevenueReconstructor;

/**
 * KPIs de receita do mês (STORY-230).
 *
 * O mês corrente sai do estado atual; o anterior é reconstruído por vigência
 * (D-02) só para a variação. Nada é persistido.
 */
final class GetRevenueKpis
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'revenue_kpis';

    public function __construct(private readonly RevenueReconstructor $reconstructor) {}

    public function handle(FinancialFilters $filters, ?CarbonImmutable $now = null): RevenueKpis
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): RevenueKpis => $this->build($filters, $now ?? CarbonImmutable::now()),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    private function build(FinancialFilters $filters, CarbonImmutable $now): RevenueKpis
    {
        $current = $this->reconstructor->forPeriod($filters->period, $filters->companyIds);
        $previousPeriod = $filters->period->previous();
        $previous = $this->reconstructor->forPeriod($previousPeriod, $filters->companyIds);

        return new RevenueKpis(
            current: $current,
            previous: $this->isComparable($previous) ? $previous : null,
            extraCreditsCents: $this->extraCreditsIn($filters->period, $filters->companyIds),
            generatedAt: $now,
        );
    }

    /**
     * Só compara com um mês que teve alguma receita.
     *
     * Um mês anterior zerado quase sempre significa "a base ainda não existia",
     * não uma queda de 100% — e mostrar essa queda no card seria alarme falso.
     */
    private function isComparable(MonthlyRevenue $previous): bool
    {
        return $previous->totalCents() > 0;
    }

    /**
     * Créditos extras pagos dentro do mês.
     *
     * Só `paid`: um pedido pendente é intenção de compra, não receita — e o
     * módulo de cobranças é onde a intenção aparece (STORY-236).
     *
     * @param  array<int, string>  $companyIds
     */
    private function extraCreditsIn(FinancialPeriod $period, array $companyIds): int
    {
        return (int) CreditOrder::query()
            ->where('status', CreditOrderStatusEnum::Paid)
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->when($companyIds !== [], fn ($query) => $query->whereIn('company_id', $companyIds))
            ->sum('amount_cents');
    }
}
