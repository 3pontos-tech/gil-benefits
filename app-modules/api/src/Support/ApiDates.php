<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Support;

use Carbon\CarbonInterface;

/**
 * Formatos de data do contrato do app (A6), usados por todos os resources.
 */
final class ApiDates
{
    /**
     * Data e hora em ISO 8601 com o offset do fuso da aplicação, ex.: 2026-10-07T10:00:00-03:00.
     */
    public static function dateTime(?CarbonInterface $value): ?string
    {
        return $value?->copy()->setTimezone((string) config('app.timezone'))->toIso8601String();
    }

    /**
     * Data pura, ex.: 2026-10-07.
     */
    public static function date(?CarbonInterface $value): ?string
    {
        return $value?->toDateString();
    }
}
