<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentsByCategory extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.metrics.appointments_by_category.heading');
    }

    protected function getData(): array
    {
        $start = $this->filters['startDate'] ? now()->parse($this->filters['startDate'])->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $this->filters['endDate'] ? now()->parse($this->filters['endDate'])->endOfDay() : now()->endOfDay();

        $results = Appointment::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('category_type')
            ->selectRaw('category_type, count(*) as total')
            ->groupBy('category_type')
            ->pluck('total', 'category_type');

        $counts = collect(AppointmentCategoryEnum::cases())
            ->mapWithKeys(fn (AppointmentCategoryEnum $category): array => [
                $category->value => $results->get($category->value, 0),
            ]);

        return [
            'datasets' => [
                [
                    'data' => $counts->values()->toArray(),
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',   // PersonalFinance - Green
                        'rgb(59, 130, 246)',  // InvestmentAdvisory - Blue
                        'rgb(168, 85, 247)',  // RetirementAndEstatePlanning - Purple
                        'rgb(249, 115, 22)',  // BusinessFinancialManagement - Orange
                        'rgb(236, 72, 153)',  // TaxPlanning - Pink
                        'rgb(20, 184, 166)',  // FundraisingAndCredit - Teal
                        'rgb(99, 102, 241)',  // MergersAndAcquisitions - Indigo
                        'rgb(239, 68, 68)',   // RiskAndCompliance - Red
                    ],
                ],
            ],
            'labels' => $counts->keys()
                ->map(fn (string $value): string => AppointmentCategoryEnum::from($value)->getLabel())
                ->all(),
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
