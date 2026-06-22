<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\ResolveScopedUserIds;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

trait HasMetricsDateRange
{
    private function dateRange(): array
    {
        $period = $this->metricsPeriod();

        return ['start' => $period->start, 'end' => $period->end];
    }

    private function metricsPeriod(): MetricsPeriod
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        if (blank($startDate) && blank($endDate)) {
            return MetricsPeriod::lastMonths(12);
        }

        return MetricsPeriod::range(
            filled($startDate) ? now()->parse($startDate) : now()->subDays(30),
            filled($endDate) ? now()->parse($endDate) : now(),
        );
    }

    private function metricsFilters(): MetricsFilters
    {
        $userId = data_get($this->filters, 'userId');
        $departmentId = data_get($this->filters, 'departmentId');

        return new MetricsFilters(
            userId: filled($userId) ? (string) $userId : null,
            departmentId: filled($departmentId) ? (string) $departmentId : null,
        );
    }

    private function filteredUserIds(): ?Collection
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        return resolve(ResolveScopedUserIds::class)->handle($tenant, $this->metricsFilters());
    }
}
