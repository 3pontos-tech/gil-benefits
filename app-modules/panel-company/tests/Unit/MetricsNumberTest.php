<?php

declare(strict_types=1);

use TresPontosTech\PanelCompany\Support\MetricsNumber;

afterEach(function (): void {
    app()->setLocale(config('app.locale'));
});

it('formats integers with locale grouping', function (string $locale, string $expected): void {
    app()->setLocale($locale);

    expect(MetricsNumber::integer(1234))->toBe($expected);
})->with([
    'pt_BR' => ['pt_BR', '1.234'],
    'en' => ['en', '1,234'],
]);

it('formats percentages without trailing zeros, locale-aware', function (): void {
    app()->setLocale('pt_BR');
    expect(MetricsNumber::percent(45.0))->toBe('45')
        ->and(MetricsNumber::percent(45.2))->toBe('45,2');

    app()->setLocale('en');
    expect(MetricsNumber::percent(45.2))->toBe('45.2');
});

it('formats decimals with a fixed single digit, locale-aware', function (): void {
    app()->setLocale('pt_BR');
    expect(MetricsNumber::decimal(4.0))->toBe('4,0');

    app()->setLocale('en');
    expect(MetricsNumber::decimal(4.0))->toBe('4.0');
});
