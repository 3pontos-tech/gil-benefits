<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

trait HasMetricsDateRange
{
    private function dateRange(): array
    {
        $month = data_get($this->filters, 'month');

        if (filled($month)) {
            [$year, $monthNumber] = explode('-', (string) $month);
            $base = now()->setDate((int) $year, (int) $monthNumber, 1);

            return [
                'start' => $base->copy()->startOfMonth(),
                'end' => $base->copy()->endOfMonth(),
            ];
        }

        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        return [
            'start' => filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
            'end' => filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay(),
        ];
    }

    private function metricsPeriod(): MetricsPeriod
    {
        $month = data_get($this->filters, 'month');

        if (filled($month)) {
            [$year, $monthNumber] = explode('-', (string) $month);

            return MetricsPeriod::month((int) $year, (int) $monthNumber);
        }

        ['start' => $start, 'end' => $end] = $this->dateRange();

        return MetricsPeriod::range($start, $end);
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
        $userId = data_get($this->filters, 'userId');

        if (filled($userId)) {
            return collect([$userId]);
        }

        $departmentId = data_get($this->filters, 'departmentId');

        if (blank($departmentId)) {
            return null;
        }

        /** @var Company $tenant */
        $tenant = Filament::getTenant();
        $tenantId = $tenant->getKey();
        $cacheKey = sprintf('metrics.department_users.%s.%s', $tenantId, $departmentId);

        return Cache::store('array')->rememberForever(
            $cacheKey,
            fn () => $tenant
                ->employees()
                ->wherePivot('department_id', $departmentId)
                ->pluck('users.id')
        );
    }
}
