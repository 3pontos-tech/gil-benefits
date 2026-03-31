<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Consultants\Models\Consultant;

class KPIsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return [
            $this->avgRatingStat(),
            $this->avgFeedbacksPerConsultantStat(),
            $this->featuredConsultantStat(),
        ];
    }

    private function dateRange(): array
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        return [
            'start' => filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
            'end' => filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay(),
        ];
    }

    private function avgRatingStat(): Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $result = AppointmentFeedback::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as avg_rating')
            ->toBase()
            ->first();

        $avg = round((float) ($result?->avg_rating ?? 0), 1);
        $total = (int) ($result?->total ?? 0);

        return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.avg_rating'), $avg . '/5')
            ->description(__('panel-admin::widgets.metrics.kpis_overview.avg_rating_description', ['total' => $total]))
            ->color($avg >= 4 ? 'success' : ($avg >= 3 ? 'warning' : 'danger'));
    }

    private function avgFeedbacksPerConsultantStat(): Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $result = AppointmentFeedback::query()
            ->join('appointments', 'appointments.id', '=', 'appointment_feedbacks.appointment_id')
            ->whereNotNull('appointments.consultant_id')
            ->whereBetween('appointment_feedbacks.created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total, COUNT(DISTINCT appointments.consultant_id) as consultant_count')
            ->toBase()
            ->first();

        $consultantCount = (int) ($result?->consultant_count ?? 0);
        $total = (int) ($result?->total ?? 0);

        $avg = $consultantCount > 0
            ? round($total / $consultantCount, 1)
            : 0;

        return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.avg_feedbacks_per_consultant'), $avg)
            ->description(__('panel-admin::widgets.metrics.kpis_overview.avg_feedbacks_per_consultant_description', ['total' => $total]))
            ->color('info');
    }

    private function featuredConsultantStat(): Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $consultant = Consultant::query()
            ->withCount(['appointments as completed_count' => fn ($query) => $query
                ->where('status', AppointmentStatus::Completed)
                ->whereBetween('created_at', [$start, $end]),
            ])
            ->withAvg('feedbacks', 'rating')
            ->whereHas('appointments', fn ($query) => $query
                ->where('status', AppointmentStatus::Completed)
                ->whereBetween('created_at', [$start, $end])
            )
            ->whereHas('feedbacks')
            ->get()
            ->sortByDesc(fn (Consultant $consultant): float => $consultant->completed_count * ($consultant->feedbacks_avg_rating ?? 0))
            ->first();

        if (! $consultant) {
            return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.featured_consultant'), '—')
                ->description(__('panel-admin::widgets.metrics.kpis_overview.no_featured_consultant'))
                ->color('gray');
        }

        $avgRating = round((float) ($consultant->feedbacks_avg_rating ?? 0), 1);
        $completedCount = (int) $consultant->completed_count;

        return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.featured_consultant'), $consultant->name)
            ->description(__('panel-admin::widgets.metrics.kpis_overview.featured_consultant_description', [
                'completed' => $completedCount,
                'rating' => $avgRating,
            ]))
            ->color('success');
    }
}
