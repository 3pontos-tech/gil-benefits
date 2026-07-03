<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Best-effort time series of credits created within the window, optionally
 * scoped to a single status. Used to render the credit KPI sparklines.
 */
final class GetCreditSeries
{
    use BuildsMetricsCacheKey;

    /**
     * @return array<int, int>
     */
    public function handle(Company $tenant, MetricsPeriod $period, ?string $status = null): array
    {
        $cacheKey = $this->metricsCacheKey('credit_series', $tenant, $period->cacheKey(), $status ?? 'all');

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $status): array {
            $query = UserCredit::forCompany($tenant)->ownedBy($tenant)
                ->when($status !== null, fn ($q) => $q->where('status', $status));

            return Trend::query($query)
                ->between(start: $period->start, end: $period->end)
                ->{$period->granularity->trendMethod()}()
                ->count()
                ->map(fn (TrendValue $value): int => (int) $value->aggregate)
                ->all();
        });
    }
}
