<?php

declare(strict_types=1);

use TresPontosTech\PanelCompany\Support\ChartGeometry;

it('builds one donut slice per value', function (): void {
    $paths = ChartGeometry::donut([10, 20, 30, 40], 54, 36);

    expect($paths)->toHaveCount(4);

    foreach ($paths as $path) {
        expect($path)->toStartWith('M')
            ->and($path)->toContain('A')
            ->and($path)->toEndWith('Z');
    }
});

it('returns empty donut slices when the total is zero', function (): void {
    $paths = ChartGeometry::donut([0, 0, 0], 54, 36);

    expect($paths)->toBe(['', '', '']);
});

it('builds gauge background and value arcs', function (): void {
    $gauge = ChartGeometry::gauge(4.7, 5.0);

    expect($gauge)->toHaveKeys(['background', 'value'])
        ->and($gauge['background'])->toStartWith('M')->toContain('A')
        ->and($gauge['value'])->toStartWith('M')->toContain('A');
});

it('scales the gauge value arc with the ratio', function (): void {
    $empty = ChartGeometry::gauge(0, 5);
    $half = ChartGeometry::gauge(2.5, 5);
    $full = ChartGeometry::gauge(5, 5);

    expect($half['value'])->not->toBe($empty['value'])
        ->and($full['value'])->not->toBe($half['value']);
});

it('clamps gauge values outside the range', function (): void {
    $over = ChartGeometry::gauge(9, 5);
    $full = ChartGeometry::gauge(5, 5);

    expect($over['value'])->toBe($full['value']);
});

it('builds a sparkline point per value', function (): void {
    $points = ChartGeometry::sparkline([1, 5, 2, 8, 3]);

    expect(explode(' ', $points))->toHaveCount(5);
});

it('handles a single-value sparkline without dividing by zero', function (): void {
    $points = ChartGeometry::sparkline([42]);

    expect($points)->not->toBeEmpty()
        ->and(explode(' ', $points))->toHaveCount(1);
});

it('places line points from left padding to width minus right padding', function (): void {
    $points = ChartGeometry::linePoints([10, 20, 30], width: 600, height: 200, maxValue: 80, padLeft: 12, padRight: 12);

    expect($points)->toHaveCount(3)
        ->and($points[0]['x'])->toBe(12.0)
        ->and($points[2]['x'])->toBe(588.0)
        ->and($points[0]['x'])->toBeLessThan($points[1]['x'])
        ->and($points[1]['x'])->toBeLessThan($points[2]['x']);
});

it('closes the area path back to the baseline', function (): void {
    $points = ChartGeometry::linePoints([10, 20, 30], width: 600, height: 200, maxValue: 80);
    $area = ChartGeometry::areaPath($points, 176);

    expect($area)->toStartWith('M')->toEndWith('Z')->toContain('176.00');
});

it('formats coordinates with a dot decimal separator (locale-safe SVG)', function (): void {
    $points = ChartGeometry::sparkline([1, 2, 3]);

    // Each "x,y" pair must use dots inside the numbers and a comma between them.
    expect($points)->toMatch('/^\d+\.\d{2},\d+\.\d{2}( \d+\.\d{2},\d+\.\d{2})*$/');
});

it('omits donut slices that are thinner than the gap', function (): void {
    // 1 out of 200 is ~0.5% (~1.8 degrees), below the 2 x gap (2.4 degrees) threshold.
    $paths = ChartGeometry::donut([199, 1], 54, 36);

    expect($paths)->toHaveCount(2)
        ->and($paths[0])->not->toBe('')
        ->and($paths[1])->toBe('');
});

it('keeps a dot decimal separator under a comma-decimal locale', function (): void {
    $previous = setlocale(LC_NUMERIC, '0');
    $changed = setlocale(LC_NUMERIC, 'pt_BR.UTF-8', 'de_DE.UTF-8', 'fr_FR.UTF-8');

    if ($changed === false) {
        test()->markTestSkipped('No comma-decimal locale available on this environment.');
    }

    try {
        $points = ChartGeometry::sparkline([1, 2, 3]);
        expect($points)->toMatch('/^\d+\.\d{2},\d+\.\d{2}( \d+\.\d{2},\d+\.\d{2})*$/');
    } finally {
        if (is_string($previous)) {
            setlocale(LC_NUMERIC, $previous);
        }
    }
});
