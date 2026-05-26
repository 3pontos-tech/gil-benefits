<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class AppointmentsByDepartmentChart extends ChartWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('panel-company::widgets.appointments_by_department.heading');
    }

    protected function getData(): array
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $selectedDepartmentId = data_get($this->filters, 'departmentId');

        $counts = Department::query()
            ->where('departments.company_id', Filament::getTenant()->id)
            ->leftJoin('company_employees', 'company_employees.department_id', '=', 'departments.id')
            ->leftJoin('appointments', function ($join) use ($start, $end): void {
                $join->on('appointments.user_id', '=', 'company_employees.user_id')
                    ->on('appointments.company_id', '=', 'company_employees.company_id')
                    ->whereBetween('appointments.appointment_at', [$start, $end]);
            })
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->select([
                'departments.id',
                'departments.name',
                DB::raw('COUNT(appointments.id) as total'),
            ])
            ->get();

        $colors = $counts
            ->map(fn ($row): string => filled($selectedDepartmentId) && $row->id === $selectedDepartmentId
                ? 'rgba(139, 92, 246, 0.9)'
                : 'rgba(59, 130, 246, 0.7)')
            ->all();

        return [
            'datasets' => [
                [
                    'label' => __('panel-company::widgets.appointments_by_department.dataset_label'),
                    'data' => $counts->pluck('total')->all(),
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $counts->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
