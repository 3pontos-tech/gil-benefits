<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

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
        $start = $this->filters['startDate'] ? now()->parse($this->filters['startDate'])->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $this->filters['endDate'] ? now()->parse($this->filters['endDate'])->endOfDay() : now()->endOfDay();

        $results = Appointment::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = collect(AppointmentStatus::cases())
            ->mapWithKeys(fn (AppointmentStatus $status): array => [
                $status->value => $results->get($status->value, 0),
            ]);

        return [
            'datasets' => [
                [
                    'data' => $counts->values()->toArray(),
                    'backgroundColor' => [
                        'rgb(156, 163, 175)',
                        'rgb(251, 191, 36)',
                        'rgb(234, 179, 8)',
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                ],
            ],
            'labels' => $counts->keys()
                ->map(fn (string $value): string => AppointmentStatus::from($value)->getLabel())
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
