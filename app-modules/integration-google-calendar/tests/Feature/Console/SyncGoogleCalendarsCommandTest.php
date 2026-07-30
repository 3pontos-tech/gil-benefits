<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\SyncConsultantCalendarJob;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CalendarEventsResponse;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    Date::setTestNow('2026-07-28 04:00:00');
});

afterEach(function (): void {
    Date::setTestNow();
});

it('dispatches a sync job for every active consultant', function (): void {
    Queue::fake();
    $consultants = Consultant::factory()->count(3)->create();

    artisan('google-calendar:sync')->assertSuccessful();

    Queue::assertPushed(SyncConsultantCalendarJob::class, 3);

    foreach ($consultants as $consultant) {
        Queue::assertPushed(
            SyncConsultantCalendarJob::class,
            fn (SyncConsultantCalendarJob $job): bool => $job->consultant->is($consultant) && ! $job->forceFullSync,
        );
    }
});

it('forces a full sync for every consultant when --full is passed', function (): void {
    Queue::fake();
    Consultant::factory()->count(2)->create(['last_full_sync_at' => Date::now()->subMinutes(5)]);

    artisan('google-calendar:sync', ['--full' => true])->assertSuccessful();

    Queue::assertPushed(SyncConsultantCalendarJob::class, 2);
    Queue::assertPushed(
        SyncConsultantCalendarJob::class,
        fn (SyncConsultantCalendarJob $job): bool => $job->forceFullSync,
    );
});

it('skips soft deleted consultants so no needless Google Calendar calls are made', function (): void {
    Queue::fake();
    Consultant::factory()->create(['email' => 'active@workspace.com']);
    Consultant::factory()->create(['email' => 'gone@workspace.com'])->delete();

    artisan('google-calendar:sync')->assertSuccessful();

    Queue::assertPushed(SyncConsultantCalendarJob::class, 1);
    Queue::assertPushed(
        SyncConsultantCalendarJob::class,
        fn (SyncConsultantCalendarJob $job): bool => $job->consultant->email === 'active@workspace.com',
    );
});

it('queues the consultants that went longest without a full sync first', function (): void {
    Queue::fake();
    $neverSynced = Consultant::factory()->create(['last_full_sync_at' => null]);
    $stale = Consultant::factory()->create(['last_full_sync_at' => Date::now()->subHours(48)]);
    $recent = Consultant::factory()->create(['last_full_sync_at' => Date::now()->subHour()]);

    artisan('google-calendar:sync')->assertSuccessful();

    $dispatchedIds = collect(Queue::pushedJobs()[SyncConsultantCalendarJob::class])
        ->map(fn (array $pushed): string => $pushed['job']->consultant->id)
        ->all();

    expect($dispatchedIds)->toBe([$neverSynced->id, $stale->id, $recent->id]);
});

it('reports how many consultants are due for a full sync', function (): void {
    Queue::fake();
    Log::spy();

    Consultant::factory()->create(['last_full_sync_at' => null]);
    Consultant::factory()->create(['last_full_sync_at' => Date::now()->subHours(30)]);
    Consultant::factory()->create(['last_full_sync_at' => Date::now()->subHours(2)]);

    artisan('google-calendar:sync')->assertSuccessful();

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Google Calendar sync dispatched'
            && $context['consultants_dispatched'] === 3
            && $context['consultants_awaiting_full_sync'] === 2
            && $context['consultants_failed'] === 0
            && $context['forced_full_sync'] === false);
});

it('counts consultants as due for a full sync using the configured interval', function (): void {
    Queue::fake();
    Log::spy();
    config()->set('google-calendar.full_sync_interval_hours', 6);

    Consultant::factory()->create(['last_full_sync_at' => Date::now()->subHours(8)]);
    Consultant::factory()->create(['last_full_sync_at' => Date::now()->subHours(2)]);

    artisan('google-calendar:sync')->assertSuccessful();

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $context['consultants_awaiting_full_sync'] === 1);
});

it('keeps syncing the remaining consultants when one of them fails', function (): void {
    $failing = Consultant::factory()->create([
        'email' => 'failing@workspace.com',
        'last_full_sync_at' => null,
    ]);
    $others = Consultant::factory()->count(2)->create([
        'last_full_sync_at' => Date::now()->subHours(48),
    ]);

    fakeClientFailingFor('failing@workspace.com');

    artisan('google-calendar:sync')->assertSuccessful();

    expect($failing->fresh()->last_full_sync_at)->toBeNull();

    foreach ($others as $other) {
        expect($other->fresh()->last_full_sync_at->toDateTimeString())->toBe('2026-07-28 04:00:00');
    }
});

it('logs the consultant and the reason when a sync fails', function (): void {
    Log::spy();

    $failing = Consultant::factory()->create([
        'email' => 'failing@workspace.com',
        'last_full_sync_at' => null,
    ]);
    Consultant::factory()->create(['last_full_sync_at' => Date::now()->subHours(48)]);

    fakeClientFailingFor('failing@workspace.com');

    artisan('google-calendar:sync')->assertSuccessful();

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $message, array $context): bool => $context['consultant_id'] === $failing->id
            && str_contains($context['reason'], 'Rate limit exceeded')
            && filled($context['failed_at']));
});

/**
 * Runs the sync inline (queue.default is "sync" while testing) with a client that
 * blows up for a single consultant and succeeds for everyone else.
 */
function fakeClientFailingFor(string $email): void
{
    $client = Mockery::mock(GoogleCalendarClient::class);

    $client->shouldReceive('getAccessToken')
        ->with($email)
        ->andThrow(new GoogleCalendarApiException('Rate limit exceeded', 429));

    $client->shouldReceive('getAccessToken')->andReturn('access');

    $client->shouldReceive('listEvents')
        ->andReturn(new CalendarEventsResponse(new Collection, null, 'next-token'));

    app()->instance(GoogleCalendarClient::class, $client);
}
