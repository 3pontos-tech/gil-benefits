<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentVolume extends ChartWidget
{
    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.metrics.appointment_volume.heading');
    }

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    protected function getFilters(): ?array
    {
        return [
            'day' => __('panel-admin::widgets.metrics.appointment_volume.filter_today'),
            'week' => __('panel-admin::widgets.metrics.appointment_volume.filter_week'),
            'month' => __('panel-admin::widgets.metrics.appointment_volume.filter_month'),
        ];
    }

    protected function getData(): array
    {
        [$start, $end, $period] = match ($this->filter ?? 'month') {
            'day' => [now()->startOfDay(), now()->endOfDay(), 'perHour'],
            'week' => [now()->startOfWeek(), now()->endOfWeek(), 'perDay'],
            default => [now()->startOfMonth(), now()->endOfMonth(), 'perDay'],
        };

        $cacheKey = "metrics.appointment_volume.{$this->filter}.{$start}.{$end}";

        [$totalData, $completedData] = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($start, $end, $period) {
            return [
                Trend::model(Appointment::class)
                    ->between(start: $start, end: $end)
                    ->$period()
                    ->count(),
                Trend::query(
                    Appointment::query()->where('status', AppointmentStatus::Completed)
                )
                    ->between(start: $start, end: $end)
                    ->$period()
                    ->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => __('panel-admin::widgets.metrics.appointment_volume.dataset_total'),
                    'data' => $totalData->map(fn (TrendValue $value): mixed => $value->aggregate)->toArray(),
                    'tension' => 0.3,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => __('panel-admin::widgets.metrics.appointment_volume.dataset_completed'),
                    'data' => $completedData->map(fn (TrendValue $value): mixed => $value->aggregate)->toArray(),
                    'tension' => 0.3,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $totalData->map(fn (TrendValue $value): string => $value->date)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
