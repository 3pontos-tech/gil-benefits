<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCategoryMix;
use TresPontosTech\PanelCompany\DTOs\CategorySlice;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\ChartGeometry;

class CategoryMixWidget extends Widget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.command-dashboard.category-mix';

    protected int|string|array $columnSpan = 3;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();
        $mix = resolve(GetCategoryMix::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        $paths = ChartGeometry::donut(
            array_map(fn (CategorySlice $slice): int => $slice->value, $mix->items),
            54,
            36,
        );

        return ['mix' => $mix, 'paths' => $paths];
    }
}
