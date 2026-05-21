<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Concerns;

trait HasMetricsDateRange
{
    private function dateRange(): array
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        return [
            'start' => filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
            'end' => filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay(),
        ];
    }
}
