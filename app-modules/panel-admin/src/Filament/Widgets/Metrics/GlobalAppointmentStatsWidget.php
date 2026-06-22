<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;

class GlobalAppointmentStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $category = data_get($this->pageFilters, 'departmentCategory');

        $base = Appointment::query()
            ->whereBetween('appointment_at', [$start, $end])
            ->when($category, fn ($q) => $q
                ->join('company_employees', function ($join): void {
                    $join->on('company_employees.user_id', '=', 'appointments.user_id')
                        ->on('company_employees.company_id', '=', 'appointments.company_id');
                })
                ->join('departments', 'departments.id', '=', 'company_employees.department_id')
                ->where('departments.category', $category)
                ->select('appointments.*')
            );

        $total = (clone $base)->count();

        $completed = (clone $base)
            ->where('appointments.status', AppointmentStatus::Completed)
            ->count();

        $cancelled = (clone $base)
            ->where('appointments.status', AppointmentStatus::Cancelled)
            ->count();

        $completionRate = $total > 0 ? round($completed / $total * 100) : 0;

        $topCompanyData = (clone $base)
            ->select('appointments.company_id', DB::raw('COUNT(*) as total'))
            ->groupBy('appointments.company_id')
            ->orderByDesc('total')
            ->toBase()
            ->first();

        $topCompany = $topCompanyData
            ? Company::query()->find($topCompanyData->company_id)
            : null;

        return array_filter([
            Stat::make(__('panel-admin::widgets.metrics.global_appointment_stats.total'), $total)
                ->description(__('panel-admin::widgets.metrics.global_appointment_stats.total_description'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make(__('panel-admin::widgets.metrics.global_appointment_stats.completed'), $completed)
                ->description(__('panel-admin::widgets.metrics.global_appointment_stats.completed_description', ['rate' => $completionRate]))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('panel-admin::widgets.metrics.global_appointment_stats.cancelled'), $cancelled)
                ->description(__('panel-admin::widgets.metrics.global_appointment_stats.cancelled_description'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($cancelled > 0 ? 'danger' : 'gray'),

            $topCompany
                ? Stat::make(__('panel-admin::widgets.metrics.global_appointment_stats.top_company'), $topCompany->name)
                    ->description(__('panel-admin::widgets.metrics.global_appointment_stats.top_company_description', ['total' => $topCompanyData->total]))
                    ->descriptionIcon('heroicon-o-building-office')
                    ->color('info')
                : null,
        ]);
    }

    private function dateRange(): array
    {
        $startDate = data_get($this->pageFilters, 'startDate');
        $endDate = data_get($this->pageFilters, 'endDate');

        return [
            'start' => filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
            'end' => filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay(),
        ];
    }
}
