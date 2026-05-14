<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

class CompanyCreditStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $company = Filament::getTenant();

        $stats = UserCredit::query()
            ->where('company_id', $company->getKey())
            ->where('owner_id', $company->owner->getKey())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $available = (int) ($stats[UserCreditStatusEnum::Available->value] ?? 0);
        $inUse = (int) ($stats[UserCreditStatusEnum::InUse->value] ?? 0);
        $used = (int) ($stats[UserCreditStatusEnum::Used->value] ?? 0);
        $total = $available + $inUse + $used;

        return [
            Stat::make(__('panel-company::widgets.credit_stats.total'), $total)
                ->icon('heroicon-o-credit-card')
                ->color('gray'),
            Stat::make(__('billing::enums.user_credit_status.available'), $available)
                ->description(__('panel-company::widgets.credit_stats.available_description'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('billing::enums.user_credit_status.in_use'), $inUse)
                ->description(__('panel-company::widgets.credit_stats.in_use_description'))
                ->descriptionIcon('heroicon-o-clock')
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make(__('billing::enums.user_credit_status.used'), $used)
                ->description(__('panel-company::widgets.credit_stats.used_description'))
                ->descriptionIcon('heroicon-o-check-badge')
                ->icon('heroicon-o-check-badge')
                ->color('gray'),
        ];
    }
}
