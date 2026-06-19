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
        $parsedMonth = $this->parsedMonthFilter();

        if ($parsedMonth !== null) {
            $base = now()->setDate($parsedMonth['year'], $parsedMonth['month'], 1);

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
        $parsedMonth = $this->parsedMonthFilter();

        if ($parsedMonth !== null) {
            return MetricsPeriod::month($parsedMonth['year'], $parsedMonth['month']);
        }

        ['start' => $start, 'end' => $end] = $this->dateRange();

        return MetricsPeriod::range($start, $end);
    }

    /**
     * Parses the `month` filter (expected `YYYY-MM`), returning null when it is
     * absent or malformed so callers fall back to the default range.
     *
     * @return array{year: int, month: int}|null
     */
    private function parsedMonthFilter(): ?array
    {
        $month = data_get($this->filters, 'month');

        if (blank($month) || preg_match('/^\d{4}-\d{1,2}$/', (string) $month) !== 1) {
            return null;
        }

        [$year, $monthNumber] = explode('-', (string) $month);
        $monthNumber = (int) $monthNumber;

        if ($monthNumber < 1 || $monthNumber > 12) {
            return null;
        }

        return ['year' => (int) $year, 'month' => $monthNumber];
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
