<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Metrics;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Models\SupportTicket;

class SupportTicketsBySector extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.metrics.support_tickets_by_sector.heading');
    }

    protected function getData(): array
    {
        $start = filled($this->pageFilters['startDate'] ?? null)
            ? now()->parse($this->pageFilters['startDate'])->startOfDay()
            : now()->subDays(30)->startOfDay();
        $end = filled($this->pageFilters['endDate'] ?? null)
            ? now()->parse($this->pageFilters['endDate'])->endOfDay()
            : now()->endOfDay();

        $byCategory = SupportTicket::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // Fold each category count into its destination channel (the "sector").
        $bySector = collect(TicketDestinationChannelEnum::cases())
            ->mapWithKeys(fn (TicketDestinationChannelEnum $channel): array => [$channel->value => 0]);

        foreach ($byCategory as $categoryValue => $total) {
            $sector = SupportTicketCategoryEnum::from($categoryValue)->getDestinationChannel();
            $bySector[$sector->value] += (int) $total;
        }

        return [
            'datasets' => [
                [
                    'data' => $bySector->values()->all(),
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',  // SupportTi - blue
                        'rgb(34, 197, 94)',   // Financial - green
                        'rgb(245, 158, 11)',  // Commercial - amber
                        'rgb(168, 85, 247)',  // Cs - purple
                        'rgb(236, 72, 153)',  // Product - pink
                    ],
                ],
            ],
            'labels' => $bySector->keys()
                ->map(fn (string $value): string => TicketDestinationChannelEnum::from($value)->getLabel())
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
