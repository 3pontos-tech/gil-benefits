<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Company\Models\DepartmentCategory;

class AppointmentsByDepartmentCategoryChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        $categoryId = data_get($this->filters, 'departmentCategoryId');

        if (filled($categoryId)) {
            $category = DepartmentCategory::query()->find($categoryId);

            return __('panel-admin::widgets.metrics.appointments_by_department_category.heading_filtered', [
                'category' => $category?->name ?? '—',
            ]);
        }

        return __('panel-admin::widgets.metrics.appointments_by_department_category.heading');
    }

    protected function getData(): array
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');
        $categoryId = data_get($this->filters, 'departmentCategoryId');

        $start = filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $end = filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay();

        if (filled($categoryId)) {
            $counts = Department::query()
                ->where('departments.category_id', $categoryId)
                ->leftJoin('company_employees', 'company_employees.department_id', '=', 'departments.id')
                ->leftJoin('appointments', function ($join) use ($start, $end): void {
                    $join->on('appointments.user_id', '=', 'company_employees.user_id')
                        ->on('appointments.company_id', '=', 'company_employees.company_id')
                        ->whereBetween('appointments.appointment_at', [$start, $end]);
                })
                ->groupBy('departments.id', 'departments.name')
                ->orderByDesc('total')
                ->select([
                    'departments.name',
                    DB::raw('COUNT(appointments.id) as total'),
                ])
                ->get();

            return $this->buildChartData($counts, color: 'rgba(139, 92, 246, 0.7)', border: 'rgb(139, 92, 246)');
        }

        $counts = DepartmentCategory::query()
            ->leftJoin('departments', 'departments.category_id', '=', 'department_categories.id')
            ->leftJoin('company_employees', 'company_employees.department_id', '=', 'departments.id')
            ->leftJoin('appointments', function ($join) use ($start, $end): void {
                $join->on('appointments.user_id', '=', 'company_employees.user_id')
                    ->on('appointments.company_id', '=', 'company_employees.company_id')
                    ->whereBetween('appointments.appointment_at', [$start, $end]);
            })
            ->groupBy('department_categories.id', 'department_categories.name')
            ->orderByDesc('total')
            ->select([
                'department_categories.name',
                DB::raw('COUNT(appointments.id) as total'),
            ])
            ->get();

        return $this->buildChartData($counts);
    }

    private function buildChartData(
        Collection $counts,
        string $color = 'rgba(59, 130, 246, 0.7)',
        string $border = 'rgb(59, 130, 246)',
    ): array {
        return [
            'datasets' => [
                [
                    'label' => __('panel-admin::widgets.metrics.appointments_by_department_category.dataset_label'),
                    'data' => $counts->pluck('total')->toArray(),
                    'backgroundColor' => $color,
                    'borderColor' => $border,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $counts->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
