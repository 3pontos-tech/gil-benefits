<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\AppointmentStats;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Appointment volume + attendance rate within the window (raw data; no SVG).
 */
final class GetAppointmentStats
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): AppointmentStats
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('appointment_stats', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): AppointmentStats {
            $base = Appointment::forCompany($tenant)
                ->betweenDates($period->start, $period->end)
                ->forUsers($userIds);

            $total = (clone $base)->count();
            $completed = (clone $base)->where('status', AppointmentStatus::Completed)->count();
            $cancelled = (clone $base)->whereIn('status', [AppointmentStatus::Cancelled, AppointmentStatus::CancelledLate])->count();

            $finalized = $completed + $cancelled;
            $attendanceRate = $finalized > 0 ? round($completed / $finalized * 100, 1) : 0.0;

            return new AppointmentStats($total, $completed, $cancelled, $attendanceRate);
        });
    }
}
