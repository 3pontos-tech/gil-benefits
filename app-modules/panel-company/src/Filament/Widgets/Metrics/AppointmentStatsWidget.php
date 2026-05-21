<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class AppointmentStatsWidget extends StatsOverviewWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $tenantId = Filament::getTenant()->id;

        $userId = data_get($this->filters, 'userId');

        $base = Appointment::query()
            ->where('company_id', $tenantId)
            ->whereBetween('appointment_at', [$start, $end])
            ->when($userId, fn ($q) => $q->where('user_id', $userId));

        $total = (clone $base)->count();

        $completed = (clone $base)
            ->where('status', AppointmentStatus::Completed)
            ->count();

        $cancelled = (clone $base)
            ->whereIn('status', [AppointmentStatus::Cancelled, AppointmentStatus::CancelledLate])
            ->count();

        $finalized = $completed + $cancelled;
        $attendanceRate = $finalized > 0 ? round($completed / $finalized * 100) : 0;

        return [
            Stat::make(__('panel-company::widgets.appointment_stats.total_scheduled'), $total)
                ->description(__('panel-company::widgets.appointment_stats.total_scheduled_description'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make(__('panel-company::widgets.appointment_stats.completed'), $completed)
                ->description(__('panel-company::widgets.appointment_stats.completed_description'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('panel-company::widgets.appointment_stats.cancelled'), $cancelled)
                ->description(__('panel-company::widgets.appointment_stats.cancelled_description'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make(__('panel-company::widgets.appointment_stats.attendance_rate'), $attendanceRate . '%')
                ->description(__('panel-company::widgets.appointment_stats.attendance_rate_description', [
                    'rate' => $attendanceRate,
                    'completed' => $completed,
                    'total' => $finalized,
                ]))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($attendanceRate >= 70 ? 'success' : ($attendanceRate >= 40 ? 'warning' : 'danger')),
        ];
    }
}
