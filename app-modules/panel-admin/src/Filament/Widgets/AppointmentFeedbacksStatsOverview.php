<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use TresPontosTech\Appointments\Models\AppointmentFeedback;

class AppointmentFeedbacksStatsOverview extends StatsOverviewWidget
{
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array<string, mixed>
     */
    public array $tableFilterState = [];

    /**
     * @var object{total: int, avg_rating: float, with_comment: int, critical: int}|null
     */
    private ?object $aggregates = null;

    /**
     * @param  array<string, mixed>  $filters
     */
    #[On('appointment-feedbacks-table-filters-changed')]
    public function syncFilters(array $filters): void
    {
        $this->tableFilterState = $filters;
    }

    protected function getStats(): array
    {
        return [
            $this->totalStat(),
            $this->avgRatingStat(),
            $this->withCommentRateStat(),
            $this->criticalStat(),
        ];
    }

    /**
     * @return object{total: int, avg_rating: float, with_comment: int, critical: int}
     */
    private function aggregates(): object
    {
        if ($this->aggregates !== null) {
            return $this->aggregates;
        }

        $row = AppointmentFeedback::query()
            ->whereHas('appointment')
            ->when(
                filled(data_get($this->tableFilterState, 'rating.values')),
                fn (Builder $q) => $q->whereIn('rating', data_get($this->tableFilterState, 'rating.values'))
            )
            ->when(
                filled(data_get($this->tableFilterState, 'company_id.value')),
                fn (Builder $q) => $q->whereHas(
                    'appointment',
                    fn (Builder $q) => $q->where('company_id', data_get($this->tableFilterState, 'company_id.value'))
                )
            )
            ->when(
                filled(data_get($this->tableFilterState, 'consultant_name.consultant_name')),
                fn (Builder $q) => $q->whereHas(
                    'appointment.consultant',
                    fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', data_get($this->tableFilterState, 'consultant_name.consultant_name')))
                )
            )
            ->when(
                filled(data_get($this->tableFilterState, 'user_name.user_name')),
                fn (Builder $q) => $q->whereHas(
                    'user',
                    fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', data_get($this->tableFilterState, 'user_name.user_name')))
                )
            )
            ->when(
                filled(data_get($this->tableFilterState, 'date_range.from')),
                fn (Builder $q) => $q->whereDate('created_at', '>=', data_get($this->tableFilterState, 'date_range.from'))
            )
            ->when(
                filled(data_get($this->tableFilterState, 'date_range.until')),
                fn (Builder $q) => $q->whereDate('created_at', '<=', data_get($this->tableFilterState, 'date_range.until'))
            )
            ->when(
                filled(data_get($this->tableFilterState, 'has_comment.value')),
                function (Builder $q) {
                    return filter_var(data_get($this->tableFilterState, 'has_comment.value'), FILTER_VALIDATE_BOOLEAN)
                        ? $q->whereNotNull('comment')->where('comment', '!=', '')
                        : $q->where(fn (Builder $q) => $q->whereNull('comment')->orWhere('comment', ''));
                }
            )
            ->when(
                filled(data_get($this->tableFilterState, 'appointment_status.values')),
                fn (Builder $q) => $q->whereHas(
                    'appointment',
                    fn (Builder $q) => $q->whereIn('status', data_get($this->tableFilterState, 'appointment_status.values'))
                )
            )
            ->selectRaw(implode(', ', [
                'count(*) as total',
                'coalesce(avg(rating), 0) as avg_rating',
                "count(*) filter (where comment is not null and comment != '') as with_comment",
                'count(*) filter (where rating <= 2) as critical',
            ]))
            ->toBase()
            ->first();

        return $this->aggregates = (object) [
            'total' => (int) ($row->total ?? 0),
            'avg_rating' => round((float) ($row->avg_rating ?? 0), 1),
            'with_comment' => (int) ($row->with_comment ?? 0),
            'critical' => (int) ($row->critical ?? 0),
        ];
    }

    private function totalStat(): Stat
    {
        $total = $this->aggregates()->total;

        return Stat::make(__('panel-admin::widgets.appointment_feedbacks_stats.total'), $total)
            ->description(__('panel-admin::widgets.appointment_feedbacks_stats.total_description'))
            ->color('gray');
    }

    private function avgRatingStat(): Stat
    {
        $avg = $this->aggregates()->avg_rating;

        return Stat::make(__('panel-admin::widgets.appointment_feedbacks_stats.avg_rating'), $avg . '/5')
            ->description(__('panel-admin::widgets.appointment_feedbacks_stats.avg_rating_description'))
            ->color($avg >= 4 ? 'success' : ($avg >= 3 ? 'warning' : 'danger'));
    }

    private function withCommentRateStat(): Stat
    {
        $total = $this->aggregates()->total;
        $withComment = $this->aggregates()->with_comment;
        $rate = $total > 0 ? round(($withComment / $total) * 100, 1) : 0;

        return Stat::make(__('panel-admin::widgets.appointment_feedbacks_stats.with_comment_rate'), $rate . '%')
            ->description(__('panel-admin::widgets.appointment_feedbacks_stats.with_comment_rate_description', [
                'count' => $withComment,
                'total' => $total,
            ]))
            ->color('info');
    }

    private function criticalStat(): Stat
    {
        $total = $this->aggregates()->total;
        $critical = $this->aggregates()->critical;
        $rate = $total > 0 ? round(($critical / $total) * 100, 1) : 0;

        return Stat::make(__('panel-admin::widgets.appointment_feedbacks_stats.critical'), $critical)
            ->description(__('panel-admin::widgets.appointment_feedbacks_stats.critical_description', [
                'rate' => $rate,
            ]))
            ->color($critical > 0 ? 'danger' : 'success');
    }
}
