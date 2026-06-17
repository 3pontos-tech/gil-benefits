<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Support;

/**
 * Pure SVG geometry helpers for the Command Dashboard charts.
 *
 * Ported from the Claude Design prototype (Frame A). Every method is a pure
 * function of its arguments — no state, no I/O — so the geometry can be unit
 * tested in isolation. Coordinates are always formatted with a dot decimal
 * separator so the resulting paths are valid SVG regardless of locale.
 */
final class ChartGeometry
{
    private const float SLICE_GAP = 1.2;

    /**
     * Build donut ring slices for a list of values.
     *
     * @param  array<int, int|float>  $values
     * @return array<int, string> SVG path `d` strings, aligned by index with $values
     */
    public static function donut(array $values, float $outerRadius, float $innerRadius, float $cx = 60, float $cy = 60): array
    {
        $total = array_sum($values);

        if ($total <= 0) {
            return array_fill(0, count($values), '');
        }

        $paths = [];
        $cumulative = 0.0;

        foreach ($values as $value) {
            $span = $value / $total * 360;
            $paths[] = $span > 2 * self::SLICE_GAP
                ? self::ringSlice($cx, $cy, $outerRadius, $innerRadius, $cumulative, $cumulative + $span)
                : '';
            $cumulative += $span;
        }

        return $paths;
    }

    /**
     * Build the background and value arcs for a half-circle gauge.
     *
     * @return array{background: string, value: string}
     */
    public static function gauge(float $value, float $max, float $cx = 100, float $cy = 100, float $radius = 80): array
    {
        $ratio = $max > 0 ? max(0.0, min(1.0, $value / $max)) : 0.0;

        return [
            'background' => self::arc($cx, $cy, $radius, -90, 90),
            'value' => self::arc($cx, $cy, $radius, -90, -90 + $ratio * 180),
        ];
    }

    /**
     * Build a sparkline polyline (`points` attribute) normalised to its own range.
     *
     * @param  array<int, int|float>  $values
     */
    public static function sparkline(array $values, float $width = 100, float $height = 32, float $padding = 3): string
    {
        $count = count($values);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return self::point($padding, $height / 2);
        }

        $min = min($values);
        $max = max($values);
        $range = ($max - $min) ?: 1;

        $points = [];

        foreach (array_values($values) as $index => $value) {
            $x = $padding + $index * (($width - 2 * $padding) / ($count - 1));
            $y = ($height - $padding) - (($value - $min) / $range) * ($height - 2 * $padding);
            $points[] = self::point($x, $y);
        }

        return implode(' ', $points);
    }

    /**
     * Build absolute line points scaled against a fixed maximum value.
     *
     * @param  array<int, int|float>  $values
     * @return array<int, array{x: float, y: float}>
     */
    public static function linePoints(
        array $values,
        float $width,
        float $height,
        float $maxValue,
        float $padLeft = 12,
        float $padRight = 12,
        float $padTop = 18,
        float $padBottom = 24,
    ): array {
        $count = count($values);

        if ($count === 0) {
            return [];
        }

        $maxValue = $maxValue ?: 1;
        $points = [];

        foreach (array_values($values) as $index => $value) {
            $x = $count === 1
                ? $padLeft
                : $padLeft + $index * (($width - $padLeft - $padRight) / ($count - 1));
            $y = ($height - $padBottom) - ($value / $maxValue) * (($height - $padTop) - $padBottom);
            $points[] = ['x' => $x, 'y' => $y];
        }

        return $points;
    }

    /**
     * Convert line points into a polyline `points` string.
     *
     * @param  array<int, array{x: float, y: float}>  $points
     */
    public static function polyline(array $points): string
    {
        return implode(' ', array_map(
            fn (array $p): string => self::point($p['x'], $p['y']),
            $points,
        ));
    }

    /**
     * Build a closed area path under a line, down to a baseline.
     *
     * @param  array<int, array{x: float, y: float}>  $points
     */
    public static function areaPath(array $points, float $baselineY): string
    {
        if ($points === []) {
            return '';
        }

        $first = $points[0];
        $last = $points[count($points) - 1];

        $d = sprintf('M%s %s', self::n($first['x']), self::n($first['y']));

        foreach (array_slice($points, 1) as $point) {
            $d .= sprintf(' L%s %s', self::n($point['x']), self::n($point['y']));
        }

        $d .= sprintf(' L%s %s', self::n($last['x']), self::n($baselineY));

        return $d . sprintf(' L%s %s Z', self::n($first['x']), self::n($baselineY));
    }

    private static function ringSlice(float $cx, float $cy, float $outer, float $inner, float $a0, float $a1): string
    {
        $gap = self::SLICE_GAP;
        $a0 += $gap;
        $a1 -= $gap;

        [$x1, $y1] = self::polar($cx, $cy, $outer, $a1);
        [$x2, $y2] = self::polar($cx, $cy, $outer, $a0);
        [$x3, $y3] = self::polar($cx, $cy, $inner, $a0);
        [$x4, $y4] = self::polar($cx, $cy, $inner, $a1);

        $large = ($a1 - $a0) <= 180 ? 0 : 1;

        return sprintf(
            'M%s %s A%s %s 0 %d 0 %s %s L%s %s A%s %s 0 %d 1 %s %s Z',
            self::n($x1), self::n($y1),
            self::n($outer), self::n($outer), $large, self::n($x2), self::n($y2),
            self::n($x3), self::n($y3),
            self::n($inner), self::n($inner), $large, self::n($x4), self::n($y4),
        );
    }

    private static function arc(float $cx, float $cy, float $radius, float $a0, float $a1): string
    {
        [$x1, $y1] = self::polar($cx, $cy, $radius, $a0);
        [$x2, $y2] = self::polar($cx, $cy, $radius, $a1);

        $large = ($a1 - $a0) <= 180 ? 0 : 1;

        return sprintf(
            'M%s %s A%s %s 0 %d 1 %s %s',
            self::n($x1), self::n($y1),
            self::n($radius), self::n($radius), $large, self::n($x2), self::n($y2),
        );
    }

    /**
     * @return array{0: float, 1: float}
     */
    private static function polar(float $cx, float $cy, float $radius, float $degrees): array
    {
        $angle = ($degrees - 90) * M_PI / 180;

        return [$cx + $radius * cos($angle), $cy + $radius * sin($angle)];
    }

    private static function point(float $x, float $y): string
    {
        return self::n($x) . ',' . self::n($y);
    }

    /**
     * Format a coordinate with a dot decimal separator (locale-safe for SVG).
     */
    private static function n(float $value): string
    {
        return sprintf('%.2f', $value);
    }
}
