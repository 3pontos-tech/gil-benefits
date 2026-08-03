<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\Concerns;

use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;

/**
 * Resolves the engagement page filters for widgets using InteractsWithPageFilters.
 */
trait HasEngagementFilters
{
    protected function engagementFilters(): EngagementFilters
    {
        /** @var array<string, mixed>|null $pageFilters */
        $pageFilters = $this->pageFilters;

        return EngagementFilters::fromPageFilters($pageFilters);
    }
}
