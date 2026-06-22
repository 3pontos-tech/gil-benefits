<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\DepartmentVolume;
use TresPontosTech\PanelCompany\DTOs\DepartmentVolumeRow;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Appointment volume per department within the window (distinct from adoption,
 * which measures the share of employees that adhered).
 */
final class GetDepartmentVolume
{
    use BuildsMetricsCacheKey;

    public function handle(Company $tenant, MetricsPeriod $period): DepartmentVolume
    {
        $cacheKey = $this->metricsCacheKey('department_volume', $tenant, $period->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period): DepartmentVolume {
            $rows = Department::query()
                ->where('departments.company_id', $tenant->getKey())
                ->leftJoin('company_employees', 'company_employees.department_id', '=', 'departments.id')
                ->leftJoin('appointments', function ($join) use ($period): void {
                    $join->on('appointments.user_id', '=', 'company_employees.user_id')
                        ->on('appointments.company_id', '=', 'company_employees.company_id')
                        ->whereBetween('appointments.appointment_at', [$period->start, $period->end]);
                })
                ->groupBy('departments.id', 'departments.name')
                ->orderByDesc('total')
                ->select(['departments.id', 'departments.name', DB::raw('COUNT(appointments.id) as total')])
                ->get()
                ->map(fn ($row): DepartmentVolumeRow => new DepartmentVolumeRow(
                    id: (string) $row->id,
                    name: (string) $row->name,
                    total: (int) $row->total,
                ))
                ->all();

            return new DepartmentVolume($rows);
        });
    }
}
