<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetEngagement;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class EngagementTilesWidget extends StatsOverviewWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $data = resolve(GetEngagement::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        $utilColor = match (true) {
            $data->utilizationRate >= 70 => 'success',
            $data->utilizationRate >= 40 => 'warning',
            default => 'danger',
        };

        return [
            Stat::make(__('panel-company::widgets.engagement_stats.active_users'), MetricsNumber::integer($data->activeUsers))
                ->description(__('panel-company::widgets.engagement_stats.active_users_description', ['count' => $data->activeUsers]))
                ->icon('heroicon-o-user-group')
                ->color('success'),
            Stat::make(__('panel-company::widgets.engagement_stats.inactive_users'), MetricsNumber::integer($data->inactiveUsers))
                ->description(__('panel-company::widgets.engagement_stats.inactive_users_description', ['count' => $data->inactiveUsers]))
                ->icon('heroicon-o-user-minus')
                ->color($data->inactiveUsers > 0 ? 'warning' : 'gray'),
            Stat::make(__('panel-company::widgets.engagement_stats.utilization_rate'), MetricsNumber::percent($data->utilizationRate) . '%')
                ->description(__('panel-company::widgets.engagement_stats.utilization_rate_description', ['rate' => MetricsNumber::percent($data->utilizationRate), 'total' => $data->totalEmployees]))
                ->icon('heroicon-o-chart-pie')
                ->color($utilColor),
            Stat::make(__('panel-company::widgets.engagement_stats.first_time_users'), MetricsNumber::integer($data->firstTimeUsers))
                ->description(__('panel-company::widgets.engagement_stats.first_time_users_description'))
                ->icon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
