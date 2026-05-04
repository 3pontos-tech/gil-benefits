<?php

namespace TresPontosTech\Admin\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentsStatsOverview extends StatsOverviewWidget
{
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    public array $tableFilterState = [];

    private ?object $aggregates = null;

    #[On('appointments-table-filters-changed')]
    public function syncFilters(array $filters): void
    {
        $this->tableFilterState = $filters;
    }

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
            ->when(
                filled(data_get($this->tableFilterState, 'company_id.value')),
                fn (Builder $q) => $q->where('company_id', data_get($this->tableFilterState, 'company_id.value'))
            )
            ->when(
                filled(data_get($this->tableFilterState, 'date_range.from')),
                fn (Builder $q) => $q->whereDate('appointment_at', '>=', data_get($this->tableFilterState, 'date_range.from'))
            )
            ->when(
                filled(data_get($this->tableFilterState, 'date_range.until')),
                fn (Builder $q) => $q->whereDate('appointment_at', '<=', data_get($this->tableFilterState, 'date_range.until'))
            )
            ->when(
                filled(data_get($this->tableFilterState, 'user_name.user_name')),
                fn (Builder $q) => $q->whereHas(
                    'user',
                    fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', data_get($this->tableFilterState, 'user_name.user_name')))
                )
            )
            ->when(
                filled(data_get($this->tableFilterState, 'consultant_name.consultant_name')),
                fn (Builder $q) => $q->whereHas(
                    'consultant',
                    fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', data_get($this->tableFilterState, 'consultant_name.consultant_name')))
                )
            )
            ->when(
                filled(data_get($this->tableFilterState, 'status.values')),
                fn (Builder $q) => $q->whereIn('status', data_get($this->tableFilterState, 'status.values'))
            )
            ->selectRaw(
                implode(', ', [
                    'count(*) as total',
                    'count(*) filter (where status = ? and consultant_id is not null) as scheduled',
                    'count(*) filter (where status = ?) as pending',
                    'count(*) filter (where status in (?, ?)) as cancelled',
                    'count(*) filter (where status = ?) as completed',
                ]),
                [
                    AppointmentStatus::Active->value,
                    AppointmentStatus::Pending->value,
                    AppointmentStatus::Cancelled->value,
                    AppointmentStatus::CancelledLate->value,
                    AppointmentStatus::Completed->value,
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
        $total = (int) $this->aggregates()->total;
        $cancelled = (int) $this->aggregates()->cancelled;
        $rate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;

        return Stat::make(__('panel-admin::widgets.appointments_stats.cancellation_rate'), $rate . '%')
            ->description(__('panel-admin::widgets.appointments_stats.cancellation_rate_description', [
                'cancelled' => $cancelled,
                'total' => $total,
            ]))
            ->color($rate >= 30 ? 'danger' : ($rate >= 15 ? 'warning' : 'success'));
    }
}
