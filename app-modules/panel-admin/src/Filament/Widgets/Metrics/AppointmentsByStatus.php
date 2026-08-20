<?php

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Metrics;

use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentsByStatus extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.metrics.appointments_by_status.heading');
    }

    protected function getData(): array
    {
        $start = $this->pageFilters['startDate'] ? now()->parse($this->pageFilters['startDate'])->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $this->pageFilters['endDate'] ? now()->parse($this->pageFilters['endDate'])->endOfDay() : now()->endOfDay();

        $results = Appointment::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = collect(AppointmentStatus::cases())
            ->mapWithKeys(fn (AppointmentStatus $status): array => [
                $status->value => $results->get($status->value, 0),
            ]);

        $statuses = $counts->keys()->map(fn (string $value): AppointmentStatus => AppointmentStatus::from($value));

        return [
            'datasets' => [
                [
                    'data' => $counts->values()->toArray(),
                    'backgroundColor' => $statuses
                        ->map(fn (AppointmentStatus $status): string => Color::convertToRgb($status->getColor()[500]))
                        ->all(),
                ],
            ],
            'labels' => $statuses->map(fn (AppointmentStatus $status): string => $status->getLabel())->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
