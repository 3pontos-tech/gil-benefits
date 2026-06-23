<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Concerns;

use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\ResolveScopedUserIds;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

trait HasMetricsDateRange
{
    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function dateRange(): array
    {
        $period = $this->metricsPeriod();

        return ['start' => $period->start, 'end' => $period->end];
    }

    private function metricsPeriod(): MetricsPeriod
    {
        $startDate = data_get($this->pageFilters, 'startDate');
        $endDate = data_get($this->pageFilters, 'endDate');

        if (blank($startDate) && blank($endDate)) {
            return MetricsPeriod::lastMonths(12);
        }

        $start = filled($startDate)
            ? now()->parse($startDate)
            : now()->parse((string) $endDate)->subDays(30);
        $end = filled($endDate)
            ? now()->parse($endDate)
            : now()->parse((string) $startDate)->addDays(30);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return MetricsPeriod::range($start, $end);
    }

    private function metricsFilters(): MetricsFilters
    {
        $userId = data_get($this->pageFilters, 'userId');
        $departmentId = data_get($this->pageFilters, 'departmentId');

        return new MetricsFilters(
            userId: filled($userId) ? (string) $userId : null,
            departmentId: filled($departmentId) ? (string) $departmentId : null,
        );
    }

    /**
     * @return Collection<int, string>|null
     */
    private function filteredUserIds(): ?Collection
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        return resolve(ResolveScopedUserIds::class)->handle($tenant, $this->metricsFilters());
    }
}
