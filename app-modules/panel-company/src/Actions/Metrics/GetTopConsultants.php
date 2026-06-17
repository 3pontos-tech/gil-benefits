<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\ConsultantRow;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Top 5 consultants by session count within the window, with completion share
 * and average rating.
 */
final class GetTopConsultants
{
    use BuildsMetricsCacheKey;

    /** @var array<int, string> */
    private const COLORS = ['primary', 'teal', 'violet', 'pink', 'amber'];

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    /**
     * @return array<int, ConsultantRow>
     */
    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): array
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('top_consultants', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): array {
            $rows = Appointment::query()
                ->where('company_id', $tenant->getKey())
                ->whereNotNull('consultant_id')
                ->whereBetween('appointment_at', [$period->start, $period->end])
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
                ->groupBy('consultant_id')
                ->selectRaw('consultant_id, count(*) as sessions, sum(case when status = ? then 1 else 0 end) as completed', [AppointmentStatus::Completed->value])
                ->orderByDesc('sessions')
                ->limit(5)
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $consultantIds = $rows->pluck('consultant_id')->all();
            $names = Consultant::query()->whereIn('id', $consultantIds)->pluck('name', 'id');

            $ratings = AppointmentFeedback::query()
                ->join('appointments', 'appointments.id', '=', 'appointment_feedbacks.appointment_id')
                ->where('appointments.company_id', $tenant->getKey())
                ->whereIn('appointments.consultant_id', $consultantIds)
                ->whereBetween('appointments.appointment_at', [$period->start, $period->end])
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('appointments.user_id', $userIds))
                ->groupBy('appointments.consultant_id')
                ->selectRaw('appointments.consultant_id as cid, avg(appointment_feedbacks.rating) as avg_rating')
                ->pluck('avg_rating', 'cid');

            $maxSessions = (int) $rows->max('sessions');

            return $rows->values()->map(function ($row, int $index) use ($names, $ratings, $maxSessions): ConsultantRow {
                $sessions = (int) $row->sessions;
                $completed = (int) $row->completed;
                $name = (string) ($names[$row->consultant_id] ?? '—');
                $rating = $ratings[$row->consultant_id] ?? null;

                return new ConsultantRow(
                    name: $name,
                    initials: $this->initials($name),
                    sessions: $sessions,
                    rating: $rating !== null ? round((float) $rating, 1) : null,
                    completionPercent: $sessions > 0 ? round($completed / $sessions * 100) : null,
                    barWidthPercent: $this->rate($sessions, $maxSessions),
                    color: self::COLORS[$index % count(self::COLORS)],
                );
            })->all();
        });
    }

    private function initials(string $name): string
    {
        return Str::of($name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    private function rate(int $value, int $total): float
    {
        return $total > 0 ? round($value / $total * 100, 1) : 0.0;
    }
}
