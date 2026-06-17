<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\DTOs\StatusBreakdown;
use TresPontosTech\PanelCompany\DTOs\StatusSegment;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Appointment status breakdown within the window (raw data; no SVG).
 */
final class GetStatusBreakdown
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    /**
     * @return array<string, string>
     */
    private function colors(): array
    {
        return [
            AppointmentStatus::Completed->value => 'emerald',
            AppointmentStatus::Active->value => 'blue',
            AppointmentStatus::Pending->value => 'amber',
            AppointmentStatus::Cancelled->value => 'red',
            AppointmentStatus::CancelledLate->value => 'orange',
        ];
    }

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): StatusBreakdown
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('status_breakdown', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): StatusBreakdown {
            $results = Appointment::query()
                ->where('company_id', $tenant->getKey())
                ->whereBetween('appointment_at', [$period->start, $period->end])
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $total = (int) $results->sum();
            $completed = (int) ($results[AppointmentStatus::Completed->value] ?? 0);
            $colors = $this->colors();

            $segments = [];

            foreach (AppointmentStatus::cases() as $status) {
                $value = (int) ($results[$status->value] ?? 0);

                if ($value === 0) {
                    continue;
                }

                $segments[] = new StatusSegment(
                    label: $status->getLabel(),
                    value: $value,
                    percent: $this->rate($value, $total),
                    color: $colors[$status->value] ?? 'neutral',
                );
            }

            return new StatusBreakdown(
                segments: $segments,
                completedPercent: $this->rate($completed, $total),
                total: $total,
            );
        });
    }

    private function rate(int $value, int $total): float
    {
        return $total > 0 ? round($value / $total * 100, 1) : 0.0;
    }
}
