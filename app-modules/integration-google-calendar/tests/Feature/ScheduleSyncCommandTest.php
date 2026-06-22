<?php

declare(strict_types=1);

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

function googleCalendarSyncScheduledEvent(): ?Event
{
    return collect(resolve(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'google-calendar:sync'));
}

it('schedules the google calendar sync command', function (): void {
    expect(googleCalendarSyncScheduledEvent())->not->toBeNull();
});

it('runs the google calendar sync every 20 minutes', function (): void {
    expect(googleCalendarSyncScheduledEvent()->expression)->toBe('*/20 * * * *');
});

it('is due at minutes 0, 20 and 40 of every hour', function (string $time, bool $expected): void {
    $expression = new CronExpression(googleCalendarSyncScheduledEvent()->expression);

    expect($expression->isDue($time))->toBe($expected);
})->with([
    'at :00' => ['2026-06-21 10:00:00', true],
    'at :10' => ['2026-06-21 10:10:00', false],
    'at :20' => ['2026-06-21 10:20:00', true],
    'at :40' => ['2026-06-21 10:40:00', true],
    'at :45' => ['2026-06-21 10:45:00', false],
]);
