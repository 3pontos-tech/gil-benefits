<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Support;

use Illuminate\Support\Number;

/**
 * Locale-aware number formatting for the dashboard widgets, honouring the
 * application locale (pt_BR / en) instead of hardcoded separators.
 */
final class MetricsNumber
{
    /**
     * Whole number with locale grouping (e.g. pt_BR "1.234", en "1,234").
     */
    public static function integer(int|float $value): string
    {
        return (string) Number::format($value, precision: 0, locale: app()->getLocale());
    }

    /**
     * Up to one decimal place, dropping trailing zeros (e.g. "45", "45,2").
     */
    public static function percent(int|float $value): string
    {
        return (string) Number::format($value, maxPrecision: 1, locale: app()->getLocale());
    }

    /**
     * Exactly one decimal place (e.g. ratings "4,0").
     */
    public static function decimal(int|float $value): string
    {
        return (string) Number::format($value, precision: 1, locale: app()->getLocale());
    }
}
