<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditTotals;

class CompanyCreditStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totals = resolve(GetCreditTotals::class)->handle(Filament::getTenant());

        return [
            Stat::make(__('panel-company::widgets.credit_stats.total'), $totals->total)
                ->icon('heroicon-o-credit-card')
                ->color('gray'),
            Stat::make(__('panel-company::widgets.credit_stats.available'), $totals->available)
                ->description(__('panel-company::widgets.credit_stats.available_description'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('panel-company::widgets.credit_stats.in_use'), $totals->inUse)
                ->description(__('panel-company::widgets.credit_stats.in_use_description'))
                ->descriptionIcon('heroicon-o-clock')
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make(__('panel-company::widgets.credit_stats.used'), $totals->used)
                ->description(__('panel-company::widgets.credit_stats.used_description'))
                ->descriptionIcon('heroicon-o-check-badge')
                ->icon('heroicon-o-check-badge')
                ->color('gray'),
        ];
    }
}
