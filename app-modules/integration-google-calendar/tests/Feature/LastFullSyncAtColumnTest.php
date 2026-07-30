<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Consultants\Models\Consultant;

it('adds a nullable last_full_sync_at timestamp to consultants', function (): void {
    expect(Schema::hasColumn('consultants', 'last_full_sync_at'))->toBeTrue()
        ->and(Schema::getColumnType('consultants', 'last_full_sync_at'))->toContain('datetime');
});

it('does not require last_full_sync_at when creating a consultant', function (): void {
    $consultant = Consultant::factory()->create();

    expect($consultant->last_full_sync_at)->toBeNull();
});

it('keeps last_full_sync_at null for consultants that never went through a full sync', function (): void {
    $consultant = Consultant::factory()->create([
        'google_calendar_synced_at' => now()->subHour(),
        'google_calendar_sync_token' => 'token',
    ]);

    expect($consultant->fresh()->last_full_sync_at)->toBeNull();
});

it('casts last_full_sync_at to a datetime', function (): void {
    $consultant = Consultant::factory()->create(['last_full_sync_at' => '2026-07-28 04:00:00']);

    expect($consultant->fresh()->last_full_sync_at)->toBeInstanceOf(Carbon::class)
        ->and($consultant->fresh()->last_full_sync_at->toDateTimeString())->toBe('2026-07-28 04:00:00');
});

it('drops last_full_sync_at on rollback without losing other consultant data', function (): void {
    $consultant = Consultant::factory()->create(['name' => 'Fernanda']);

    $migration = require base_path('app-modules/integration-google-calendar/database/migrations/2026_07_28_115922_add_last_full_sync_at_to_consultants.php');

    $migration->down();

    expect(Schema::hasColumn('consultants', 'last_full_sync_at'))->toBeFalse()
        ->and(Consultant::query()->whereKey($consultant->getKey())->value('name'))->toBe('Fernanda');

    $migration->up();

    expect(Schema::hasColumn('consultants', 'last_full_sync_at'))->toBeTrue();
});
