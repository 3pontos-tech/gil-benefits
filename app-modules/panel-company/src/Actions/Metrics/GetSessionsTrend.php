<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\DTOs\SessionsTrend as SessionsTrendData;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;
use TresPontosTech\PanelCompany\Support\TrendGranularity;

/**
 * Total vs completed session counts across the window, as raw series + labels.
 * Presentation (SVG line for the home, Chart.js datasets for Metrics) is left
 * to the consuming widget.
 */
final class GetSessionsTrend
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): SessionsTrendData
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('sessions_trend', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): SessionsTrendData {
            $method = $period->granularity->trendMethod();

            $totalSeries = Trend::query(
                Appointment::query()
                    ->where('company_id', $tenant->getKey())
                    ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds)),
            )->between(start: $period->start, end: $period->end)->{$method}()->count();

            $completedSeries = Trend::query(
                Appointment::query()
                    ->where('company_id', $tenant->getKey())
                    ->where('status', AppointmentStatus::Completed->value)
                    ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds)),
            )->between(start: $period->start, end: $period->end)->{$method}()->count();

            $totalValues = $totalSeries->map(fn (TrendValue $v): int => (int) $v->aggregate)->all();
            $completedValues = $completedSeries->map(fn (TrendValue $v): int => (int) $v->aggregate)->all();

            $labels = $totalSeries
                ->values()
                ->map(fn (TrendValue $v): string => $this->label($v->date, $period->granularity))
                ->all();

            $firstNonZero = collect($completedValues)->first(fn (int $v): bool => $v > 0);
            $last = $completedValues === [] ? 0 : (int) end($completedValues);
            $growthFactor = null;

            if (($firstNonZero ?? 0) > 0 && $last > 0) {
                $factor = round($last / $firstNonZero, 1);
                $growthFactor = $factor > 1.0 ? $factor : null;
            }

            return new SessionsTrendData(
                totalSeries: $totalValues,
                completedSeries: $completedValues,
                labels: $labels,
                completedTotal: array_sum($completedValues),
                growthFactor: $growthFactor,
            );
        });
    }

    private function label(string $date, TrendGranularity $granularity): string
    {
        return match ($granularity) {
            TrendGranularity::PerMonth => Str::lower(str_replace('.', '', Date::parse($date . '-01')->translatedFormat('M'))),
            TrendGranularity::PerDay => Date::parse($date)->translatedFormat('d/m'),
            TrendGranularity::PerHour => Date::parse($date)->translatedFormat('H\h'),
        };
    }
}
