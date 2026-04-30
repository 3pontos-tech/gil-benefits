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
