<?php

namespace TresPontosTech\Consultants\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class ConsultantStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 3;

    protected function getStats(): array
    {
        $consultantId = auth()->user()->consultant->getKey();

        $trendData = Trend::query(
            Appointment::query()
                ->where('consultant_id', $consultantId)
                ->where('status', AppointmentStatus::Completed->value)
        )
            ->between(
                start: now()->subDays(29),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            Stat::make(
                __('panel-consultant::widgets.stats_overview.label'),
                $trendData->sum('aggregate')
            )

                ->description(__('panel-consultant::widgets.stats_overview.description'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($trendData->pluck('aggregate')->toArray())
                ->color('success'),
        ];
    }
}
