<?php

use Illuminate\Support\Facades\Log;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Actions\CreateCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CreateEventResponse;

it('calls CreateCalendarEventAction', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Active]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-token');
    $mockClient->shouldReceive('createEvent')->once()->andReturn(new CreateEventResponse(
        eventId: 'event-123',
        meetLink: null,
    ));

    $action = new CreateCalendarEventAction($mockClient);
    $job = new CreateAppointmentCalendarEventJob($appointment);
    $job->handle($action);

    expect($appointment->refresh()->google_event_id)->toBe('event-123');
});

it('logs warning and does not throw for non-retryable exceptions', function (): void {
    Log::spy();

    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Active]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->andThrow(new GoogleCalendarApiException('Not in workspace', retryable: false));

    $action = new CreateCalendarEventAction($mockClient);
    $job = new CreateAppointmentCalendarEventJob($appointment);
    $job->handle($action);

    Log::shouldHaveReceived('warning')->once();
});

it('rethrows retryable exceptions', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Active]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->andThrow(new GoogleCalendarApiException('API error', 500));

    $action = new CreateCalendarEventAction($mockClient);
    $job = new CreateAppointmentCalendarEventJob($appointment);
    $job->handle($action);
})->throws(GoogleCalendarApiException::class);

it('has correct retry configuration', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Active]);
    $job = new CreateAppointmentCalendarEventJob($appointment);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([10, 60, 300]);
});
