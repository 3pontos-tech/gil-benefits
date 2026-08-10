<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Engagement;

use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;
use TresPontosTech\PanelAdmin\DTOs\EngagementFunnelRow;
use TresPontosTech\PanelAdmin\DTOs\EngagementTotals;

/**
 * Folds the per-company funnel into the consolidated figures shown on the
 * engagement report header.
 */
final class GetEngagementTotals
{
    public function __construct(private readonly GetEngagementFunnel $funnel) {}

    public function handle(EngagementFilters $filters): EngagementTotals
    {
        $rows = $this->funnel->handle($filters);

        return new EngagementTotals(
            seats: (int) $rows->sum(fn (EngagementFunnelRow $row): int => $row->seats),
            registered: (int) $rows->sum(fn (EngagementFunnelRow $row): int => $row->registered),
            withAppointment: (int) $rows->sum(fn (EngagementFunnelRow $row): int => $row->withAppointment),
            withCompletedAppointment: (int) $rows->sum(fn (EngagementFunnelRow $row): int => $row->withCompletedAppointment),
            withRecurrence: (int) $rows->sum(fn (EngagementFunnelRow $row): int => $row->withRecurrence),
        );
    }
}
