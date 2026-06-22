<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetAppointmentStats;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class AppointmentStatsTilesWidget extends StatsOverviewWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $stats = resolve(GetAppointmentStats::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        $attendanceColor = match (true) {
            $stats->attendanceRate >= 70 => 'success',
            $stats->attendanceRate >= 40 => 'warning',
            default => 'danger',
        };

        return [
            Stat::make(__('panel-company::widgets.appointment_stats.total_scheduled'), MetricsNumber::integer($stats->total))
                ->description(__('panel-company::widgets.appointment_stats.total_scheduled_description'))
                ->icon('heroicon-o-calendar')
                ->color('primary'),
            Stat::make(__('panel-company::widgets.appointment_stats.completed'), MetricsNumber::integer($stats->completed))
                ->description(__('panel-company::widgets.appointment_stats.completed_description'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('panel-company::widgets.appointment_stats.cancelled'), MetricsNumber::integer($stats->cancelled))
                ->description(__('panel-company::widgets.appointment_stats.cancelled_description'))
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('panel-company::widgets.appointment_stats.attendance_rate'), MetricsNumber::percent($stats->attendanceRate) . '%')
                ->description(__('panel-company::widgets.appointment_stats.attendance_rate_description', [
                    'rate' => MetricsNumber::percent($stats->attendanceRate),
                    'completed' => $stats->completed,
                    'total' => $stats->completed + $stats->cancelled,
                ]))
                ->icon('heroicon-o-chart-bar')
                ->color($attendanceColor),
        ];
    }
}
