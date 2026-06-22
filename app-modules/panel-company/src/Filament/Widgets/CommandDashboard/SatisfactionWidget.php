<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetSatisfaction;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\ChartGeometry;

class SatisfactionWidget extends Widget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.command-dashboard.satisfaction';

    protected int|string|array $columnSpan = 4;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();
        $data = resolve(GetSatisfaction::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());
        $gauge = ChartGeometry::gauge($data->avg, 5);

        return [
            'data' => $data,
            'gaugeBackground' => $gauge['background'],
            'gaugeValue' => $gauge['value'],
        ];
    }
}
