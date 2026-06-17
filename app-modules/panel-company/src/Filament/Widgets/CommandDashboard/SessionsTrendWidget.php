<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetSessionsTrend;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\ChartGeometry;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

class SessionsTrendWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.command-dashboard.sessions-trend';

    protected int|string|array $columnSpan = 6;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();
        $trend = resolve(GetSessionsTrend::class)->handle($tenant, MetricsPeriod::lastMonths(12), MetricsFilters::none());

        $width = 600.0;
        $height = 200.0;
        $baseline = 176.0;
        $max = max([1, ...$trend->totalSeries, ...$trend->completedSeries]);

        $totalPoints = ChartGeometry::linePoints($trend->totalSeries, $width, $height, (float) $max);
        $completedPoints = ChartGeometry::linePoints($trend->completedSeries, $width, $height, (float) $max);

        $count = max(1, count($trend->labels));
        $labels = [];

        foreach (array_values($trend->labels) as $i => $label) {
            $x = $count === 1 ? 12.0 : 12.0 + $i * (($width - 24.0) / ($count - 1));
            $labels[] = ['label' => $label, 'x' => $x];
        }

        return [
            'completedTotal' => $trend->completedTotal,
            'growthFactor' => $trend->growthFactor,
            'totalPolyline' => ChartGeometry::polyline($totalPoints),
            'completedPolyline' => ChartGeometry::polyline($completedPoints),
            'completedArea' => ChartGeometry::areaPath($completedPoints, $baseline),
            'labels' => $labels,
        ];
    }
}
