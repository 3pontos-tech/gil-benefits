<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Models\SupportTicket;

class SupportTicketsByCategory extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.metrics.support_tickets_by_category.heading');
    }

    protected function getData(): array
    {
        $start = filled($this->pageFilters['startDate'] ?? null)
            ? now()->parse($this->pageFilters['startDate'])->startOfDay()
            : now()->subDays(30)->startOfDay();
        $end = filled($this->pageFilters['endDate'] ?? null)
            ? now()->parse($this->pageFilters['endDate'])->endOfDay()
            : now()->endOfDay();

        $results = SupportTicket::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $counts = collect(SupportTicketCategoryEnum::cases())
            ->mapWithKeys(fn (SupportTicketCategoryEnum $category): array => [
                $category->value => $results->get($category->value, 0),
            ]);

        return [
            'datasets' => [
                [
                    'data' => $counts->values()->toArray(),
                    'backgroundColor' => 'rgb(241, 120, 90)',
                ],
            ],
            'labels' => $counts->keys()
                ->map(fn (string $value): string => SupportTicketCategoryEnum::from($value)->getLabel())
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
