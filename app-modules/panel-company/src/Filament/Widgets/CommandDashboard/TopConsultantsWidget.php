<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetTopConsultants;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

class TopConsultantsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.command-dashboard.top-consultants';

    protected int|string|array $columnSpan = 4;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        return ['consultants' => resolve(GetTopConsultants::class)->handle($tenant, MetricsPeriod::lastMonths(12), MetricsFilters::none())];
    }
}
