<?php

declare(strict_types=1);

use TresPontosTech\PanelCompany\Support\MetricsPeriod;
use TresPontosTech\PanelCompany\Support\TrendGranularity;

it('builds a last-12-months window per month', function (): void {
    $this->travelTo('2026-06-16');
    $period = MetricsPeriod::lastMonths(12);

    expect($period->start->toDateString())->toBe('2025-07-01')
        ->and($period->end->toDateString())->toBe('2026-06-30')
        ->and($period->granularity)->toBe(TrendGranularity::PerMonth)
        ->and($period->cacheKey())->toBe('2025-07-01_2026-06-30_PerMonth');
});

it('includes the granularity in the cache key to avoid collisions', function (): void {
    $start = now()->parse('2026-06-01')->toImmutable();
    $end = now()->parse('2026-06-30')->toImmutable();

    $daily = new MetricsPeriod($start, $end, TrendGranularity::PerDay);
    $monthly = new MetricsPeriod($start, $end, TrendGranularity::PerMonth);

    expect($daily->cacheKey())->not->toBe($monthly->cacheKey());
});

it('rejects a non-positive month count', function (): void {
    expect(fn (): MetricsPeriod => MetricsPeriod::lastMonths(0))->toThrow(InvalidArgumentException::class);
});

it('builds a single month window per day', function (): void {
    $period = MetricsPeriod::month(2026, 2);

    expect($period->start->toDateString())->toBe('2026-02-01')
        ->and($period->end->toDateString())->toBe('2026-02-28')
        ->and($period->granularity)->toBe(TrendGranularity::PerDay);
});

it('builds a custom range deriving granularity from the span', function (): void {
    $period = MetricsPeriod::range(now()->parse('2026-01-01'), now()->parse('2026-03-31'));

    expect($period->granularity)->toBe(TrendGranularity::PerMonth);
});
