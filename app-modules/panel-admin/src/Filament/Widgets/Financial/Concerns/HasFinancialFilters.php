<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns;

use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Resolve os filtros da página financeira para widgets que usam
 * `InteractsWithPageFilters`. Espelha o `HasEngagementFilters`.
 */
trait HasFinancialFilters
{
    protected function financialFilters(): FinancialFilters
    {
        /** @var array<string, mixed>|null $pageFilters */
        $pageFilters = $this->pageFilters;

        return FinancialFilters::fromPageFilters($pageFilters);
    }
}
