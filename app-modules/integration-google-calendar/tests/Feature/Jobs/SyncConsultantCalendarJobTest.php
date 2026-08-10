<?php

use Illuminate\Support\Facades\Log;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\RemoveCancelledGoogleEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\RemoveStaleBlockedSchedulesAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\SyncConsultantCalendarAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpsertBlockedScheduleAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\SyncConsultantCalendarJob;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CalendarEventsResponse;

function makeSyncAction(GoogleCalendarClient $client): SyncConsultantCalendarAction
{
    return new SyncConsultantCalendarAction(
        $client,
        new UpsertBlockedScheduleAction,
        new RemoveCancelledGoogleEventAction,
        new RemoveStaleBlockedSchedulesAction,
    );
}

beforeEach(function (): void {
    $this->consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $this->job = new SyncConsultantCalendarJob($this->consultant);
});

it('calls SyncConsultantCalendarAction successfully', function (): void {
    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->andReturn('fake-token');
    $client->shouldReceive('listEvents')->once()->andReturn(new CalendarEventsResponse(collect([]), null, null));

    $this->job->handle(makeSyncAction($client));
});

it('logs a warning and does not rethrow for non-retryable exceptions', function (): void {
    Log::spy();

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')
        ->andThrow(new GoogleCalendarApiException('Not in Google Workspace', retryable: false));

    $this->job->handle(makeSyncAction($client));

    Log::shouldHaveReceived('warning')->once();
});

it('rethrows retryable exceptions so the queue can retry', function (): void {
    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')
        ->andThrow(new GoogleCalendarApiException('Rate limit exceeded', 429));

    expect(fn () => $this->job->handle(makeSyncAction($client)))->toThrow(GoogleCalendarApiException::class);
});

it('has the correct retry configuration', function (): void {
    expect($this->job->tries)->toBe(3)
        ->and($this->job->backoff)->toBe([10, 60, 300]);
});

it('defaults to not forcing a full sync', function (): void {
    expect($this->job->forceFullSync)->toBeFalse();
});

it('forwards the force full sync flag to the action', function (): void {
    $this->consultant->update([
        'google_calendar_sync_token' => 'fresh-token',
        'google_calendar_synced_at' => now()->subMinutes(2),
        'last_full_sync_at' => now()->subMinutes(2),
    ]);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->andReturn('fake-token');
    $client->shouldReceive('listEvents')
        ->once()
        ->withArgs(fn ($accessToken, $calendarId, $timeMin = null, $timeMax = null, $pageToken = null, $syncToken = null): bool => $syncToken === null && filled($timeMin) && filled($timeMax))
        ->andReturn(new CalendarEventsResponse(collect([]), null, 'forced-token'));

    (new SyncConsultantCalendarJob($this->consultant, forceFullSync: true))->handle(makeSyncAction($client));

    expect($this->consultant->fresh()->google_calendar_sync_token)->toBe('forced-token');
});

it('logs the consultant and the reason when the job definitively fails', function (): void {
    Log::spy();

    $this->job->failed(new GoogleCalendarApiException('Rate limit exceeded', 429));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Google Calendar sync failed for consultant'
            && $context['consultant_id'] === $this->consultant->id
            && $context['reason'] === 'Rate limit exceeded'
            && filled($context['failed_at']));
});

it('scrubs the consultant email out of failure logs', function (): void {
    Log::spy();

    $this->job->failed(new GoogleCalendarApiException('Invalid grant for consultant@workspace.com'));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $context['reason'] === 'Invalid grant for [email]');
});
