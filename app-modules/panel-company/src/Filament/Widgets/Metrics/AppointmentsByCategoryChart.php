<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class AppointmentsByCategoryChart extends ChartWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('panel-company::widgets.appointments_by_category.heading');
    }

    protected function getData(): array
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $userIds = $this->filteredUserIds();

        $results = Appointment::query()
            ->where('company_id', Filament::getTenant()->id)
            ->whereBetween('appointment_at', [$start, $end])
            ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
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
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(249, 115, 22)',
                        'rgb(236, 72, 153)',
                        'rgb(20, 184, 166)',
                        'rgb(99, 102, 241)',
                        'rgb(239, 68, 68)',
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
