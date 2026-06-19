<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCategoryMix;
use TresPontosTech\PanelCompany\DTOs\CategorySlice;
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
        $mix = resolve(GetCategoryMix::class)->handle(
            Filament::getTenant(),
            $this->metricsPeriod(),
            $this->metricsFilters(),
        );

        $palette = [
            'rgb(34, 197, 94)',
            'rgb(59, 130, 246)',
            'rgb(168, 85, 247)',
            'rgb(249, 115, 22)',
            'rgb(236, 72, 153)',
            'rgb(20, 184, 166)',
            'rgb(99, 102, 241)',
            'rgb(239, 68, 68)',
        ];

        return [
            'datasets' => [
                [
                    'data' => array_map(fn (CategorySlice $item): int => $item->value, $mix->items),
                    'backgroundColor' => array_map(
                        fn (int $index): string => $palette[$index % count($palette)],
                        array_keys($mix->items),
                    ),
                ],
            ],
            'labels' => array_map(fn (CategorySlice $item): string => $item->label, $mix->items),
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
