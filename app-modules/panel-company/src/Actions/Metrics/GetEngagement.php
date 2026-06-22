<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\EngagementData;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Employee engagement within the window: active/inactive counts, utilization
 * rate and first-time users (raw data; no SVG).
 */
final class GetEngagement
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): EngagementData
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('engagement', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): EngagementData {
            $employeesQuery = $tenant->employees()
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('users.id', $userIds));

            $totalEmployees = (clone $employeesQuery)->count();

            $activeUsers = (clone $employeesQuery)
                ->whereHas('appointments', fn ($q) => $q
                    ->forCompany($tenant)
                    ->betweenDates($period->start, $period->end)
                    ->forUsers($userIds))
                ->count();

            $inactiveUsers = $totalEmployees - $activeUsers;
            $utilizationRate = $totalEmployees > 0 ? round($activeUsers / $totalEmployees * 100, 1) : 0.0;

            $firstTimeUsers = (clone $employeesQuery)
                ->whereHas('appointments', fn ($q) => $q
                    ->forCompany($tenant)
                    ->betweenDates($period->start, $period->end)
                    ->forUsers($userIds))
                ->whereDoesntHave('appointments', fn ($q) => $q
                    ->forCompany($tenant)
                    ->forUsers($userIds)
                    ->where('appointment_at', '<', $period->start))
                ->count();

            return new EngagementData($totalEmployees, $activeUsers, $inactiveUsers, $utilizationRate, $firstTimeUsers);
        });
    }
}
