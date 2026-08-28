<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialPeriod;
use TresPontosTech\PanelAdmin\DTOs\Financial\MonthlyRevenue;
use TresPontosTech\PanelAdmin\DTOs\Financial\RevenueSeriesPoint;
use TresPontosTech\PanelAdmin\Support\RevenueReconstructor;

/**
 * Evolução mensal da receita (STORY-231).
 *
 * Cada mês é reconstruído por vigência com o preço de hoje (D-02). Só o mês
 * corrente sai do estado atual sem reconstrução — os demais são estimativa, e
 * a flag `isReconstructed` existe para a tela dizer isso ao usuário.
 */
final class GetRevenueSeries
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'revenue_series';

    public function __construct(private readonly RevenueReconstructor $reconstructor) {}

    /**
     * @return Collection<int, RevenueSeriesPoint>
     */
    public function handle(FinancialFilters $filters, int $months = 12, ?CarbonImmutable $now = null): Collection
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET . ".{$months}", $filters),
            $this->financialCacheTtl(),
            fn (): Collection => $this->build($filters, $months, $now ?? CarbonImmutable::now()),
        );
    }

    public function forget(FinancialFilters $filters, int $months = 12): void
    {
        $this->forgetFinancialCache(self::BUCKET . ".{$months}", $filters);
    }

    /**
     * @return Collection<int, RevenueSeriesPoint>
     */
    private function build(FinancialFilters $filters, int $months, CarbonImmutable $now): Collection
    {
        $currentMonthKey = $now->format('Y-m');
        $previous = null;

        return collect(FinancialPeriod::lastMonths($months, $now)->eachMonth())
            ->map(function (FinancialPeriod $month) use ($filters, $currentMonthKey, &$previous): RevenueSeriesPoint {
                $revenue = $this->reconstructor->forPeriod($month, $filters->companyIds);
                $point = $this->pointFor($month, $revenue, $previous, $currentMonthKey);
                $previous = $revenue;

                return $point;
            })
            ->values();
    }

    private function pointFor(
        FinancialPeriod $month,
        MonthlyRevenue $revenue,
        ?MonthlyRevenue $previous,
        string $currentMonthKey,
    ): RevenueSeriesPoint {
        return new RevenueSeriesPoint(
            label: $month->label(),
            totalCents: $revenue->totalCents(),
            b2bCents: $revenue->b2bCents,
            standaloneCents: $revenue->standaloneCents,
            variation: $this->variation($revenue, $previous),
            isReconstructed: $month->start->format('Y-m') !== $currentMonthKey,
        );
    }

    /**
     * Variação contra o mês anterior da própria série.
     *
     * Nula no primeiro ponto e quando o mês anterior foi zero — dividir por zero
     * não tem resposta, e exibir "+100%" sobre uma base inexistente diria que a
     * receita dobrou quando na verdade ela começou.
     */
    private function variation(MonthlyRevenue $current, ?MonthlyRevenue $previous): ?float
    {
        return $current->variationAgainst($previous);
    }
}
