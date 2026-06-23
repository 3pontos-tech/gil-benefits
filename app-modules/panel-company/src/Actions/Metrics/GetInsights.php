<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\InsightsData;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\DTOs\TopUser;
use TresPontosTech\PanelCompany\DTOs\VolumeVariation;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Cross-cutting insights within the window: never-used share, volume variation
 * vs the preceding equal-length window, and the top employee (raw data).
 */
final class GetInsights
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): InsightsData
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('insights', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $filters, $userIds): InsightsData {
            $employeesQuery = $tenant->employees()
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('users.id', $userIds));

            $totalEmployees = (clone $employeesQuery)->count();
            $everUsed = (clone $employeesQuery)
                ->whereHas('appointments', function (Builder $q) use ($tenant, $userIds): void {
                    /** @var Builder<Appointment> $q */
                    $q->forCompany($tenant)->forUsers($userIds);
                })
                ->count();

            $neverUsedCount = max(0, $totalEmployees - $everUsed);
            $neverUsedRate = $totalEmployees > 0 ? round($neverUsedCount / $totalEmployees * 100, 1) : 0.0;

            $durationDays = (int) $period->start->diffInDays($period->end);
            $prevEnd = $period->start->subDay();
            $prevStart = $prevEnd->subDays($durationDays);

            $current = $this->volume($tenant, $userIds, $period->start, $period->end);
            $previous = $this->volume($tenant, $userIds, $prevStart, $prevEnd);
            $variation = $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;

            $topUser = null;

            if (blank($filters->userId)) {
                $employeeIds = $userIds ?? $tenant->employees()->pluck('users.id');

                $top = Appointment::forCompany($tenant)
                    ->betweenDates($period->start, $period->end)
                    ->forUsers($employeeIds)
                    ->selectRaw('user_id, count(*) as period_count')
                    ->groupBy('user_id')
                    ->orderByDesc('period_count')
                    ->toBase()
                    ->first();

                if ($top !== null) {
                    $name = (string) ($tenant->employees()->find($top->user_id)->name ?? '—');
                    $topUser = new TopUser($name, (int) $top->period_count);
                }
            }

            return new InsightsData(
                neverUsedCount: $neverUsedCount,
                totalEmployees: $totalEmployees,
                neverUsedRate: $neverUsedRate,
                volume: new VolumeVariation($current, $previous, $variation),
                topUser: $topUser,
            );
        });
    }

    /**
     * @param  Collection<int, string>|null  $userIds
     */
    private function volume(Company $tenant, ?Collection $userIds, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return Appointment::forCompany($tenant)
            ->betweenDates($start, $end)
            ->forUsers($userIds)
            ->count();
    }
}
