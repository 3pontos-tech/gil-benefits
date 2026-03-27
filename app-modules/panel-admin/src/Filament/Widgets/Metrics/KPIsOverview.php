<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;

class KPIsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return [
            $this->conclusionRateStat(),
            $this->cancellationRateStat(),
            $this->pendingAppointmentsStat(),
            $this->avgAppointmentsPerConsultantStat(),
            $this->appointmentsLastSevenDaysStat(),
        ];
    }

    private function dateRange(): array
    {
        return [
            'start' => $this->filters['startDate'] ? now()->parse($this->filters['startDate'])->startOfDay() : now()->subDays(30)->startOfDay(),
            'end' => $this->filters['endDate'] ? now()->parse($this->filters['endDate'])->endOfDay() : now()->endOfDay(),
        ];
    }

    private function conclusionRateStat(): Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $result = Appointment::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('count(*) as total, count(*) filter (where status = ?) as completed', [
                AppointmentStatus::Completed->value,
            ])
            ->first();

        $total = (int) $result->total;
        $completed = (int) $result->completed;
        $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.conclusion_rate'), "{$rate}%")
            ->description(__('panel-admin::widgets.metrics.kpis_overview.conclusion_rate_description', [
                'completed' => $completed,
                'total' => $total,
            ]))
            ->color($rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'danger'));
    }

    private function cancellationRateStat(): Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $result = Appointment::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', [AppointmentStatus::Draft])
            ->selectRaw('count(*) as total, count(*) filter (where status = ?) as cancelled', [
                AppointmentStatus::Cancelled->value,
            ])
            ->first();

        $total = (int) $result->total;
        $cancelled = (int) $result->cancelled;
        $rate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;

        return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.cancellation_rate'), "{$rate}%")
            ->description(__('panel-admin::widgets.metrics.kpis_overview.cancellation_rate_description', [
                'cancelled' => $cancelled,
                'total' => $total,
            ]))
            ->color($rate >= 30 ? 'danger' : ($rate >= 15 ? 'warning' : 'success'));
    }

    private function pendingAppointmentsStat(): Stat
    {
        $pending = Appointment::query()
            ->whereIn('status', [
                AppointmentStatus::Pending,
                AppointmentStatus::Scheduling,
                AppointmentStatus::Active,
            ])
            ->count();

        return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.pending'), $pending)
            ->description(__('panel-admin::widgets.metrics.kpis_overview.pending_description'))
            ->color('warning');
    }

    private function avgAppointmentsPerConsultantStat(): Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $activeAppointments = Appointment::query()
            ->whereNotNull('consultant_id')
            ->whereNotIn('status', [
                AppointmentStatus::Completed,
                AppointmentStatus::Cancelled,
            ])
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $totalConsultants = Consultant::query()
            ->whereHas('appointments', fn ($query) => $query
                ->whereNotIn('status', [
                    AppointmentStatus::Completed,
                    AppointmentStatus::Cancelled,
                ])
                ->whereBetween('created_at', [$start, $end])
            )
            ->count();

        $avg = $totalConsultants > 0 ? round($activeAppointments / $totalConsultants, 1) : 0;

        return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.avg_per_consultant'), $avg)
            ->description(__('panel-admin::widgets.metrics.kpis_overview.avg_per_consultant_description'))
            ->color('info');
    }

    private function appointmentsLastSevenDaysStat(): Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $count = Appointment::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return Stat::make(__('panel-admin::widgets.metrics.kpis_overview.last_seven_days'), $count)
            ->description(__('panel-admin::widgets.metrics.kpis_overview.last_seven_days_description'))
            ->color('success');
    }
}
