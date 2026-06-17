<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\DepartmentBar;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Adoption per department: share of active employees with at least one
 * appointment in the window. (This is adoption, not raw volume.)
 */
final class GetDepartmentAdoption
{
    use BuildsMetricsCacheKey;

    /**
     * @return array<int, DepartmentBar>
     */
    public function handle(Company $tenant, MetricsPeriod $period): array
    {
        $cacheKey = $this->metricsCacheKey('department_adoption', $tenant, $period->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period): array {
            $totals = DB::table('company_employees')
                ->where('company_id', $tenant->getKey())
                ->whereNotNull('department_id')
                ->where('active', true)
                ->groupBy('department_id')
                ->selectRaw('department_id, count(*) as total')
                ->pluck('total', 'department_id');

            if ($totals->isEmpty()) {
                return [];
            }

            $adopted = DB::table('company_employees as ce')
                ->join('appointments as a', function ($join) use ($period): void {
                    $join->on('a.user_id', '=', 'ce.user_id')
                        ->on('a.company_id', '=', 'ce.company_id')
                        ->whereBetween('a.appointment_at', [$period->start, $period->end]);
                })
                ->where('ce.company_id', $tenant->getKey())
                ->whereNotNull('ce.department_id')
                ->where('ce.active', true)
                ->groupBy('ce.department_id')
                ->selectRaw('ce.department_id, count(distinct ce.user_id) as adopted')
                ->pluck('adopted', 'department_id');

            $names = $tenant->departments()->pluck('name', 'id');

            return $totals
                ->map(function (int $total, string $departmentId) use ($adopted, $names): DepartmentBar {
                    $adoptedCount = (int) ($adopted[$departmentId] ?? 0);

                    return new DepartmentBar(
                        label: (string) ($names[$departmentId] ?? '—'),
                        adopted: $adoptedCount,
                        total: $total,
                        percent: $this->rate($adoptedCount, $total),
                    );
                })
                ->sortByDesc('percent')
                ->values()
                ->all();
        });
    }

    private function rate(int $value, int $total): float
    {
        return $total > 0 ? round($value / $total * 100, 1) : 0.0;
    }
}
