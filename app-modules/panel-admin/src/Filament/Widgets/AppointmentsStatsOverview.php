<?php

namespace TresPontosTech\Admin\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentsStatsOverview extends StatsOverviewWidget
{
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    private ?object $aggregates = null;

    protected function getStats(): array
    {
        return [
            $this->totalRequestsStat(),
            $this->scheduledStat(),
            $this->pendingStat(),
            $this->cancellationsStat(),
            $this->conclusionRateStat(),
            $this->cancellationRateStat(),
        ];
    }

    private function aggregates(): object
    {
        return $this->aggregates ??= Appointment::query()
            ->selectRaw(
                implode(', ', [
                    'count(*) as total',
                    'count(*) filter (where status = ? and consultant_id is not null) as scheduled',
                    'count(*) filter (where status in (?, ?, ?)) as pending',
                    'count(*) filter (where status = ?) as cancelled',
                    'count(*) filter (where status = ?) as completed',
                    'count(*) filter (where status != ?) as non_draft',
                ]),
                [
                    AppointmentStatus::Active->value,
                    AppointmentStatus::Pending->value,
                    AppointmentStatus::Scheduling->value,
                    AppointmentStatus::Active->value,
                    AppointmentStatus::Cancelled->value,
                    AppointmentStatus::Completed->value,
                    AppointmentStatus::Draft->value,
                ]
            )
            ->first();
    }

    private function totalRequestsStat(): Stat
    {
        $total = (int) $this->aggregates()->total;

        return Stat::make(__('panel-admin::widgets.appointments_stats.total_requests'), $total)
            ->description(__('panel-admin::widgets.appointments_stats.total_requests_description'))
            ->color('gray');
    }

    private function scheduledStat(): Stat
    {
        $scheduled = (int) $this->aggregates()->scheduled;

        return Stat::make(__('panel-admin::widgets.appointments_stats.scheduled'), $scheduled)
            ->description(__('panel-admin::widgets.appointments_stats.scheduled_description'))
            ->color('info');
    }

    private function pendingStat(): Stat
    {
        $pending = (int) $this->aggregates()->pending;

        return Stat::make(__('panel-admin::widgets.appointments_stats.pending'), $pending)
            ->description(__('panel-admin::widgets.appointments_stats.pending_description'))
            ->color('warning');
    }

    private function cancellationsStat(): Stat
    {
        $cancelled = (int) $this->aggregates()->cancelled;

        return Stat::make(__('panel-admin::widgets.appointments_stats.cancellations'), $cancelled)
            ->description(__('panel-admin::widgets.appointments_stats.cancellations_description'))
            ->color('danger');
    }

    private function conclusionRateStat(): Stat
    {
        $total = (int) $this->aggregates()->total;
        $completed = (int) $this->aggregates()->completed;
        $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        return Stat::make(__('panel-admin::widgets.appointments_stats.conclusion_rate'), $rate . '%')
            ->description(__('panel-admin::widgets.appointments_stats.conclusion_rate_description', [
                'completed' => $completed,
                'total' => $total,
            ]))
            ->color($rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'danger'));
    }

    private function cancellationRateStat(): Stat
    {
        $nonDraft = (int) $this->aggregates()->non_draft;
        $cancelled = (int) $this->aggregates()->cancelled;
        $rate = $nonDraft > 0 ? round(($cancelled / $nonDraft) * 100, 1) : 0;

        return Stat::make(__('panel-admin::widgets.appointments_stats.cancellation_rate'), $rate . '%')
            ->description(__('panel-admin::widgets.appointments_stats.cancellation_rate_description', [
                'cancelled' => $cancelled,
                'total' => $nonDraft,
            ]))
            ->color($rate >= 30 ? 'danger' : ($rate >= 15 ? 'warning' : 'success'));
    }
}
