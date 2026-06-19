<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
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
            $totals = $tenant->employees()
                ->wherePivot('active', true)
                ->wherePivotNotNull('department_id')
                ->groupBy('company_employees.department_id')
                ->selectRaw('company_employees.department_id as department_id, count(distinct users.id) as total')
                ->pluck('total', 'department_id');

            if ($totals->isEmpty()) {
                return [];
            }

            $adopted = Appointment::query()
                ->join('company_employees as ce', function ($join): void {
                    $join->on('ce.user_id', '=', 'appointments.user_id')
                        ->on('ce.company_id', '=', 'appointments.company_id');
                })
                ->where('appointments.company_id', $tenant->getKey())
                ->whereBetween('appointments.appointment_at', [$period->start, $period->end])
                ->whereNotNull('ce.department_id')
                ->where('ce.active', true)
                ->groupBy('ce.department_id')
                ->selectRaw('ce.department_id as department_id, count(distinct ce.user_id) as adopted')
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
