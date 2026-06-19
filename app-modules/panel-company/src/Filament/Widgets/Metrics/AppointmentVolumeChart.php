<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\PanelCompany\Actions\Metrics\GetSessionsTrend;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class AppointmentVolumeChart extends ChartWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('panel-company::widgets.appointment_volume.heading');
    }

    protected function getData(): array
    {
        $trend = resolve(GetSessionsTrend::class)->handle(
            Filament::getTenant(),
            $this->metricsPeriod(),
            $this->metricsFilters(),
        );

        return [
            'datasets' => [
                [
                    'label' => __('panel-company::widgets.appointment_volume.dataset_total'),
                    'data' => $trend->totalSeries,
                    'tension' => 0.3,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => __('panel-company::widgets.appointment_volume.dataset_completed'),
                    'data' => $trend->completedSeries,
                    'tension' => 0.3,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $trend->labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
