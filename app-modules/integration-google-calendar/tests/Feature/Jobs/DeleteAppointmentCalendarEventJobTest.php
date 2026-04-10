<?php

use Illuminate\Support\Facades\Log;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Actions\DeleteCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

it('calls DeleteCalendarEventAction', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Cancelled,
        'google_event_id' => 'event-123',
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-token');
    $mockClient->shouldReceive('deleteEvent')->once();

    $action = new DeleteCalendarEventAction($mockClient);
    $job = new DeleteAppointmentCalendarEventJob($appointment);
    $job->handle($action);

    expect($appointment->refresh()->google_event_id)->toBeNull();
});

it('logs warning and does not throw for non-retryable exceptions', function (): void {
    Log::spy();

    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Cancelled,
        'google_event_id' => 'event-123',
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->andThrow(new GoogleCalendarApiException('Not in workspace', retryable: false));

    $action = new DeleteCalendarEventAction($mockClient);
    $job = new DeleteAppointmentCalendarEventJob($appointment);
    $job->handle($action);

    Log::shouldHaveReceived('warning')->once();
});

it('rethrows retryable exceptions', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Cancelled,
        'google_event_id' => 'event-123',
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->andThrow(new GoogleCalendarApiException('API error', 500));

    $action = new DeleteCalendarEventAction($mockClient);
    $job = new DeleteAppointmentCalendarEventJob($appointment);
    $job->handle($action);
})->throws(GoogleCalendarApiException::class);
