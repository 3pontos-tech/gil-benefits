<?php

declare(strict_types=1);

use TresPontosTech\PanelCompany\Support\TrendGranularity;

it('maps each case to the Flowframe Trend method name', function (): void {
    expect(TrendGranularity::PerHour->trendMethod())->toBe('perHour')
        ->and(TrendGranularity::PerDay->trendMethod())->toBe('perDay')
        ->and(TrendGranularity::PerMonth->trendMethod())->toBe('perMonth');
});

it('derives granularity from a day span', function (): void {
    expect(TrendGranularity::forDays(1))->toBe(TrendGranularity::PerHour)
        ->and(TrendGranularity::forDays(31))->toBe(TrendGranularity::PerDay)
        ->and(TrendGranularity::forDays(120))->toBe(TrendGranularity::PerMonth);
});
