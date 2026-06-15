<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

class SupportTicketsByStatus extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.metrics.support_tickets_by_status.heading');
    }

    protected function getData(): array
    {
        $start = filled($this->filters['startDate'] ?? null)
            ? now()->parse($this->filters['startDate'])->startOfDay()
            : now()->subDays(30)->startOfDay();
        $end = filled($this->filters['endDate'] ?? null)
            ? now()->parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        $results = SupportTicket::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = collect(SupportTicketStatusEnum::cases())
            ->mapWithKeys(fn (SupportTicketStatusEnum $status): array => [
                $status->value => $results->get($status->value, 0),
            ]);

        return [
            'datasets' => [
                [
                    'data' => $counts->values()->toArray(),
                    'backgroundColor' => [
                        'rgb(245, 158, 11)',  // Pending - amber
                        'rgb(59, 130, 246)',  // Dispatched - blue
                        'rgb(239, 68, 68)',   // Failed - red
                        'rgb(34, 197, 94)',   // Resolved - green
                        'rgb(107, 114, 128)', // Closed - gray
                    ],
                ],
            ],
            'labels' => $counts->keys()
                ->map(fn (string $value): string => SupportTicketStatusEnum::from($value)->getLabel())
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
