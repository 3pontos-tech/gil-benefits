<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\DTOs\SatisfactionData;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Satisfaction metrics from appointment feedback within the window: average
 * rating, NPS (1-5 scale) and recommendation share. (Raw data; no gauge SVG.)
 */
final class GetSatisfaction
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): SatisfactionData
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('satisfaction', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): SatisfactionData {
            $distribution = AppointmentFeedback::query()
                ->join('appointments', 'appointments.id', '=', 'appointment_feedbacks.appointment_id')
                ->where('appointments.company_id', $tenant->getKey())
                ->whereBetween('appointments.appointment_at', [$period->start, $period->end])
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('appointments.user_id', $userIds))
                ->groupBy('appointment_feedbacks.rating')
                ->selectRaw('appointment_feedbacks.rating as rating, count(*) as total')
                ->pluck('total', 'rating');

            $total = (int) $distribution->sum();

            if ($total === 0) {
                return new SatisfactionData(avg: 0.0, total: 0, nps: 0, recommend: 0.0);
            }

            $weighted = $distribution->reduce(fn (int $carry, int $count, int $rating): int => $carry + $rating * $count, 0);
            $promoters = (int) ($distribution[5] ?? 0);
            $detractors = (int) ($distribution[1] ?? 0) + (int) ($distribution[2] ?? 0) + (int) ($distribution[3] ?? 0);
            $recommend = (int) ($distribution[4] ?? 0) + (int) ($distribution[5] ?? 0);

            return new SatisfactionData(
                avg: round($weighted / $total, 1),
                total: $total,
                nps: (int) round(($promoters - $detractors) / $total * 100),
                recommend: $this->rate($recommend, $total),
            );
        });
    }

    private function rate(int $value, int $total): float
    {
        return $total > 0 ? round($value / $total * 100, 1) : 0.0;
    }
}
