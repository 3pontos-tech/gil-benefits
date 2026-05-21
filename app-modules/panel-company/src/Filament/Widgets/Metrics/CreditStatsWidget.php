<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

class CreditStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $tenantId = Filament::getTenant()->id;
        $userId = data_get($this->filters, 'userId');

        $base = UserCredit::query()
            ->where('company_id', $tenantId)
            ->when($userId, fn ($q) => $q->where('holder_id', $userId));

        $distributed = (clone $base)
            ->whereNotNull('transferred_at')
            ->whereBetween('transferred_at', [$start, $end])
            ->count();

        $usedInPeriod = (clone $base)
            ->where('status', UserCreditStatusEnum::Used)
            ->whereHas('appointment', fn ($q) => $q->whereBetween('appointment_at', [$start, $end]))
            ->count();

        $inUse = (clone $base)
            ->where('status', UserCreditStatusEnum::InUse)
            ->count();

        $available = (clone $base)
            ->where('status', UserCreditStatusEnum::Available)
            ->count();

        return [
            Stat::make(__('panel-company::widgets.credit_stats_metrics.distributed'), $distributed)
                ->description(__('panel-company::widgets.credit_stats_metrics.distributed_description'))
                ->descriptionIcon('heroicon-o-arrow-right-circle')
                ->color('primary'),

            Stat::make(__('panel-company::widgets.credit_stats_metrics.used_in_period'), $usedInPeriod)
                ->description(__('panel-company::widgets.credit_stats_metrics.used_in_period_description'))
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make(__('panel-company::widgets.credit_stats_metrics.in_use'), $inUse)
                ->description(__('panel-company::widgets.credit_stats_metrics.in_use_description'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),

            Stat::make(__('panel-company::widgets.credit_stats_metrics.available'), $available)
                ->description(__('panel-company::widgets.credit_stats_metrics.available_description'))
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('gray'),
        ];
    }

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
