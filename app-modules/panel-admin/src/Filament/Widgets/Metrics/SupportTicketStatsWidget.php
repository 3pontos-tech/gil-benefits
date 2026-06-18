<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

class SupportTicketStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $counts = SupportTicket::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();
        $resolved = (int) $counts->get(SupportTicketStatusEnum::Resolved->value, 0)
            + (int) $counts->get(SupportTicketStatusEnum::Closed->value, 0);
        $open = (int) $counts->get(SupportTicketStatusEnum::Pending->value, 0)
            + (int) $counts->get(SupportTicketStatusEnum::InProgress->value, 0);

        // Delivery failures live on the destination, not the ticket lifecycle.
        $failed = TicketDestination::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', TicketDestinationStatusEnum::Failed)
            ->count();

        $resolutionRate = $total > 0 ? (int) round($resolved / $total * 100) : 0;

        return [
            Stat::make(__('panel-admin::widgets.metrics.support_ticket_stats.total'), $total)
                ->description(__('panel-admin::widgets.metrics.support_ticket_stats.total_description'))
                ->descriptionIcon('heroicon-o-inbox-stack')
                ->color('primary'),

            Stat::make(__('panel-admin::widgets.metrics.support_ticket_stats.resolved'), $resolved)
                ->description(__('panel-admin::widgets.metrics.support_ticket_stats.resolved_description', ['rate' => $resolutionRate]))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('panel-admin::widgets.metrics.support_ticket_stats.open'), $open)
                ->description(__('panel-admin::widgets.metrics.support_ticket_stats.open_description'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(__('panel-admin::widgets.metrics.support_ticket_stats.failed'), $failed)
                ->description(__('panel-admin::widgets.metrics.support_ticket_stats.failed_description'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($failed > 0 ? 'danger' : 'gray'),
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function dateRange(): array
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        return [
            'start' => filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
            'end' => filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay(),
        ];
    }
}
