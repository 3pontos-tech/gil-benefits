<?php

declare(strict_types=1);

use TresPontosTech\PanelCompany\DTOs\MetricsFilters;

it('represents an empty filter set', function (): void {
    expect(MetricsFilters::none()->cacheKey())->toBe('all')
        ->and(MetricsFilters::none()->isScoped())->toBeFalse();
});

it('builds a deterministic cache key from user and department', function (): void {
    expect((new MetricsFilters(userId: '7'))->cacheKey())->toBe('u:7')
        ->and((new MetricsFilters(departmentId: '3'))->cacheKey())->toBe('d:3')
        ->and((new MetricsFilters(userId: '7', departmentId: '3'))->isScoped())->toBeTrue();
});
