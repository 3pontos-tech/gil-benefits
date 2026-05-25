<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
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
        $tenantId = Filament::getTenant()->id;
        $userIds = $this->filteredUserIds();

        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        if (filled($startDate) && filled($endDate)) {
            $start = now()->parse($startDate)->startOfDay();
            $end = now()->parse($endDate)->endOfDay();
        } else {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
        }

        $span = $start->diffInDays($end);
        $period = match (true) {
            $span <= 1 => 'perHour',
            $span <= 31 => 'perDay',
            default => 'perMonth',
        };

        $filterKey = $userIds instanceof Collection ? $userIds->implode(',') : 'all';
        $cacheKey = sprintf('company_metrics.appt_volume.%s.%s.%s.%s', $tenantId, $filterKey, $start, $end);

        [$totalData, $completedData] = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tenantId, $userIds, $start, $end, $period): array {
            return [
                Trend::query(
                    Appointment::query()
                        ->where('company_id', $tenantId)
                        ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
                )
                    ->between(start: $start, end: $end)
                    ->$period()
                    ->count(),
                Trend::query(
                    Appointment::query()
                        ->where('company_id', $tenantId)
                        ->where('status', AppointmentStatus::Completed)
                        ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
                )
                    ->between(start: $start, end: $end)
                    ->$period()
                    ->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => __('panel-company::widgets.appointment_volume.dataset_total'),
                    'data' => $totalData->map(fn (TrendValue $value): mixed => $value->aggregate)->toArray(),
                    'tension' => 0.3,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => __('panel-company::widgets.appointment_volume.dataset_completed'),
                    'data' => $completedData->map(fn (TrendValue $value): mixed => $value->aggregate)->toArray(),
                    'tension' => 0.3,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $totalData->map(fn (TrendValue $value): string => $value->date)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
