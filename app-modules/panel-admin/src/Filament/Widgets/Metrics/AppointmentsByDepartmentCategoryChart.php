<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Enums\DepartmentCategory;
use TresPontosTech\Company\Models\Department;

class AppointmentsByDepartmentCategoryChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        $category = data_get($this->filters, 'departmentCategory');

        if (filled($category)) {
            $enum = DepartmentCategory::tryFrom($category);

            return __('panel-admin::widgets.metrics.appointments_by_department_category.heading_filtered', [
                'category' => $enum?->getLabel() ?? '—',
            ]);
        }

        return __('panel-admin::widgets.metrics.appointments_by_department_category.heading');
    }

    protected function getData(): array
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');
        $category = data_get($this->filters, 'departmentCategory');

        $start = filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $end = filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay();

        $query = Department::query()
            ->leftJoin('company_employees', 'company_employees.department_id', '=', 'departments.id')
            ->leftJoin('appointments', function ($join) use ($start, $end): void {
                $join->on('appointments.user_id', '=', 'company_employees.user_id')
                    ->on('appointments.company_id', '=', 'company_employees.company_id')
                    ->whereBetween('appointments.appointment_at', [$start, $end]);
            });

        if (filled($category)) {
            $counts = $query
                ->where('departments.category', $category)
                ->groupBy('departments.id', 'departments.name')
                ->orderByDesc('total')
                ->select([
                    'departments.name',
                    DB::raw('COUNT(appointments.id) as total'),
                ])
                ->get();

            return $this->buildChartData($counts, color: 'rgba(139, 92, 246, 0.7)', border: 'rgb(139, 92, 246)');
        }

        $counts = $query
            ->groupBy('departments.category')
            ->orderByDesc('total')
            ->select([
                'departments.category',
                DB::raw('COUNT(appointments.id) as total'),
            ])
            ->get()
            ->map(fn ($row) => (object) [
                'name' => $row->category->getLabel(),
                'total' => $row->total,
            ]);

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
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
