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

class EngagementInsightsTilesWidget extends StatsOverviewWidget
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

        $data = resolve(GetInsights::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        $variation = $data->volume->variation;

        [$variationLabel, $variationColor, $variationIcon] = match (true) {
            $variation === null => [__('panel-company::widgets.insights.volume_stable'), 'gray', 'heroicon-o-minus'],
            $variation > 0 => [__('panel-company::widgets.insights.volume_increase', ['rate' => abs($variation)]), 'success', 'heroicon-o-arrow-trending-up'],
            $variation < 0 => [__('panel-company::widgets.insights.volume_decrease', ['rate' => abs($variation)]), 'danger', 'heroicon-o-arrow-trending-down'],
            default => [__('panel-company::widgets.insights.volume_stable'), 'gray', 'heroicon-o-minus'],
        };

        $stats = [
            Stat::make($variationLabel, MetricsNumber::integer($data->volume->current))
                ->description(__('panel-company::widgets.insights.volume_comparison_description', ['current' => $data->volume->current, 'previous' => $data->volume->previous]))
                ->icon($variationIcon)
                ->columnSpanFull()
                ->color($variationColor),
        ];

        if ($data->topUser !== null) {
            $stats[] = Stat::make(__('panel-company::widgets.insights.top_user'), $data->topUser->name)
                ->description(__('panel-company::widgets.insights.top_user_description', ['name' => $data->topUser->name, 'count' => $data->topUser->count]))
                ->icon('heroicon-o-trophy')
                ->columnSpanFull()
                ->color('info');
        }

        return $stats;
    }
}
