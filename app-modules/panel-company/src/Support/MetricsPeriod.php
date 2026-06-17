<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Immutable description of a metrics window: bounds, granularity and a stable
 * cache key. Unifies the previously scattered period logic (last-12-months in
 * the dashboard builder, the 30-day default and the span-based granularity in
 * the Metrics widgets).
 */
final readonly class MetricsPeriod
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public TrendGranularity $granularity,
    ) {}

    public static function lastMonths(int $months = 12): self
    {
        $end = now()->toImmutable()->endOfMonth();
        $start = now()->toImmutable()->subMonthsNoOverflow($months - 1)->startOfMonth();

        return new self($start, $end, TrendGranularity::PerMonth);
    }

    public static function month(int $year, int $month): self
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();

        return new self($start, $start->endOfMonth(), TrendGranularity::PerDay);
    }

    public static function range(CarbonInterface $start, CarbonInterface $end): self
    {
        $start = $start->toImmutable()->startOfDay();
        $end = $end->toImmutable()->endOfDay();

        return new self($start, $end, TrendGranularity::forDays((int) $start->diffInDays($end)));
    }

    public function cacheKey(): string
    {
        return sprintf('%s_%s', $this->start->toDateString(), $this->end->toDateString());
    }
}
