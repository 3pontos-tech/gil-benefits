<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use TresPontosTech\Billing\Core\Support\QuotaCycle;

it('anchors the cycle on the contract day', function (): void {
    $cycle = QuotaCycle::forAnchor(
        CarbonImmutable::parse('2026-03-10'),
        CarbonImmutable::parse('2026-09-15 14:00'),
    );

    expect($cycle->start->toDateString())->toBe('2026-09-10')
        ->and($cycle->end->toDateString())->toBe('2026-10-10');
});

it('clamps short months without drifting', function (): void {
    $anchor = CarbonImmutable::parse('2026-01-31');

    $starts = collect(['2026-02-01', '2026-03-01', '2026-04-01', '2026-05-01', '2026-06-01'])
        ->map(fn (string $at): string => QuotaCycle::forAnchor($anchor, CarbonImmutable::parse($at))->start->toDateString());

    expect($starts->all())->toBe(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31']);
});

it('treats the turn instant as the start of the new cycle', function (): void {
    $anchor = CarbonImmutable::parse('2026-03-10');

    $before = QuotaCycle::forAnchor($anchor, CarbonImmutable::parse('2026-09-09 23:59:59'));
    $after = QuotaCycle::forAnchor($anchor, CarbonImmutable::parse('2026-09-10 00:00:00'));

    expect($before->start->toDateString())->toBe('2026-08-10')
        ->and($after->start->toDateString())->toBe('2026-09-10');
});

it('returns the first cycle when the anchor is still in the future', function (): void {
    $cycle = QuotaCycle::forAnchor(
        CarbonImmutable::parse('2026-12-01'),
        CarbonImmutable::parse('2026-08-10'),
    );

    expect($cycle->start->toDateString())->toBe('2026-12-01')
        ->and($cycle->end->toDateString())->toBe('2027-01-01');
});

it('contains only moments inside the window', function (): void {
    $cycle = QuotaCycle::forAnchor(
        CarbonImmutable::parse('2026-03-10'),
        CarbonImmutable::parse('2026-09-15'),
    );

    expect($cycle->contains(CarbonImmutable::parse('2026-09-10 00:00')))->toBeTrue()
        ->and($cycle->contains(CarbonImmutable::parse('2026-10-09 23:59')))->toBeTrue()
        ->and($cycle->contains(CarbonImmutable::parse('2026-10-10 00:00')))->toBeFalse()
        ->and($cycle->contains(CarbonImmutable::parse('2026-09-09 23:59')))->toBeFalse();
});

it('knows whether the cycle has already closed', function (): void {
    $cycle = QuotaCycle::forAnchor(
        CarbonImmutable::parse('2026-03-10'),
        CarbonImmutable::parse('2026-09-15'),
    );

    expect($cycle->hasClosed(CarbonImmutable::parse('2026-10-09 23:59')))->toBeFalse()
        ->and($cycle->hasClosed(CarbonImmutable::parse('2026-10-10 00:00')))->toBeTrue();
});
