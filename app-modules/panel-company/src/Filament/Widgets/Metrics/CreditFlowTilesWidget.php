<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditFlow;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class CreditFlowTilesWidget extends StatsOverviewWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 12;

    protected int|array|null $columns = 2;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $flow = resolve(GetCreditFlow::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        return [
            Stat::make(__('panel-company::widgets.credit_stats_metrics.distributed'), MetricsNumber::integer($flow->distributed))
                ->description(__('panel-company::widgets.credit_stats_metrics.distributed_description'))
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary'),
            Stat::make(__('panel-company::widgets.credit_stats_metrics.used_in_period'), MetricsNumber::integer($flow->usedInPeriod))
                ->description(__('panel-company::widgets.credit_stats_metrics.used_in_period_description'))
                ->icon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }
}
