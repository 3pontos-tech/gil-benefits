<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Support;

use Illuminate\Support\Number;

/**
 * Locale-aware number formatting for the engagement report, honouring the
 * application locale (pt_BR / en) instead of hardcoded separators.
 */
final class EngagementNumber
{
    /**
     * Placeholder rendered whenever a rate has no meaningful denominator.
     */
    public const string EMPTY = '—';

    /**
     * Whole number with locale grouping (e.g. pt_BR "1.234", en "1,234").
     */
    public static function integer(int|float $value): string
    {
        return (string) Number::format($value, precision: 0, locale: app()->getLocale());
    }

    /**
     * Percentage with up to one decimal place, or a dash when undefined.
     */
    public static function percent(?float $value): string
    {
        if ($value === null) {
            return self::EMPTY;
        }

        return Number::format($value, maxPrecision: 1, locale: app()->getLocale()) . '%';
    }

    /**
     * Rate of a funnel step over the previous one, or null when the previous
     * step has no records (avoids division by zero and misleading zeroes).
     */
    public static function rate(int $value, int $total): ?float
    {
        if ($total <= 0) {
            return null;
        }

        return round($value / $total * 100, 1);
    }
}
