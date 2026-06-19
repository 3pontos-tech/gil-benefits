<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCategoryMix;
use TresPontosTech\PanelCompany\DTOs\CategorySlice;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\ChartGeometry;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

class CategoryMixWidget extends Widget
{
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
        $mix = resolve(GetCategoryMix::class)->handle($tenant, MetricsPeriod::lastMonths(12), MetricsFilters::none());

        $paths = ChartGeometry::donut(
            array_map(fn (CategorySlice $slice): int => $slice->value, $mix->items),
            54,
            36,
        );

        return ['mix' => $mix, 'paths' => $paths];
    }
}
