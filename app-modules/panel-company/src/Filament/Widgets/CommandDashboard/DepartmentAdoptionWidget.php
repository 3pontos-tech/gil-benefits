<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetDepartmentAdoption;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

class DepartmentAdoptionWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.command-dashboard.department-adoption';

    protected int|string|array $columnSpan = 5;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        return ['departments' => resolve(GetDepartmentAdoption::class)->handle($tenant, MetricsPeriod::lastMonths(12))];
    }
}
