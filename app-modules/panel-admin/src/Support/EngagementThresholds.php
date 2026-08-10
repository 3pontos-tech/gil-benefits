<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Support;

/**
 * Alert thresholds of the engagement report, as agreed with the business team.
 */
final class EngagementThresholds
{
    /**
     * A company below this completion rate (held / booked) is flagged as
     * critical in the funnel table.
     */
    public const float COMPANY_COMPLETION_RATE = 50.0;

    /**
     * A week below this completion rate (held / booked) is flagged as critical
     * in the weekly table and chart.
     */
    public const float WEEKLY_COMPLETION_RATE = 60.0;
}
