<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Support;

/**
 * Granularity of a metrics time series, mapped to the Flowframe Trend method.
 */
enum TrendGranularity
{
    case PerHour;
    case PerDay;
    case PerMonth;

    public function trendMethod(): string
    {
        return match ($this) {
            self::PerHour => 'perHour',
            self::PerDay => 'perDay',
            self::PerMonth => 'perMonth',
        };
    }

    public static function forDays(int $days): self
    {
        return match (true) {
            $days <= 1 => self::PerHour,
            $days <= 31 => self::PerDay,
            default => self::PerMonth,
        };
    }
}
