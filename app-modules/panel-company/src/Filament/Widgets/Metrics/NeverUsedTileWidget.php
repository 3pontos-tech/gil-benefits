<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetInsights;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class NeverUsedTileWidget extends StatsOverviewWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $data = resolve(GetInsights::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        $color = match (true) {
            $data->neverUsedRate > 50 => 'danger',
            $data->neverUsedRate > 20 => 'warning',
            default => 'success',
        };

        return [
            Stat::make(
                __('panel-company::widgets.insights.not_used_benefit', ['rate' => MetricsNumber::percent($data->neverUsedRate)]),
                $data->neverUsedCount . '/' . $data->totalEmployees,
            )
                ->description(__('panel-company::widgets.insights.not_used_benefit_description', ['count' => $data->neverUsedCount, 'total' => $data->totalEmployees]))
                ->icon('heroicon-o-exclamation-circle')
                ->columnSpanFull()
                ->color($color),
        ];
    }
}
