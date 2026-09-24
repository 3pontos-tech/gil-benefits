<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

class UserCreditStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 4;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $stats = UserCredit::query()
            ->where('holder_id', auth()->id())
            ->where('company_id', Filament::getTenant()?->getKey())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $available = (int) ($stats[UserCreditStatusEnum::Available->value] ?? 0);
        $inUse = (int) ($stats[UserCreditStatusEnum::InUse->value] ?? 0);
        $used = (int) ($stats[UserCreditStatusEnum::Used->value] ?? 0);
        $total = $available + $inUse + $used;

        return [
            Stat::make(__('panel-app::widgets.credit_stats.total'), $total)
                ->icon(Heroicon::OutlinedCreditCard)
                ->extraAttributes(['class' => 'fi-apt-stat-primary']),
            Stat::make(__('panel-app::widgets.credit_stats.available'), $available)
                ->icon(Heroicon::OutlinedCheckCircle)
                ->extraAttributes(['class' => 'fi-apt-stat-emerald']),
            Stat::make(__('panel-app::widgets.credit_stats.in_use'), $inUse)
                ->icon(Heroicon::OutlinedArrowPath)
                ->extraAttributes(['class' => 'fi-apt-stat-blue']),
            Stat::make(__('panel-app::widgets.credit_stats.used'), $used)
                ->icon(Heroicon::OutlinedArrowPath)
                ->extraAttributes(['class' => 'fi-apt-stat-muted']),
        ];
    }
}
