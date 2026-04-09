<?php

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\CreateCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CreateEventResponse;

beforeEach(function (): void {
    $this->consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $this->appointment = Appointment::factory()->create([
        'consultant_id' => $this->consultant->id,
        'status' => AppointmentStatus::Active,
    ]);
});

it('creates google calendar event and saves google_event_id and meeting_url', function (): void {
    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->with('consultant@workspace.com')
        ->andReturn('fake-access-token');

    $mockClient->shouldReceive('createEvent')
        ->once()
        ->andReturn(new CreateEventResponse(
            eventId: 'google-event-abc123',
            meetLink: 'https://meet.google.com/abc-defg-hij',
        ));

    $action = new CreateCalendarEventAction($mockClient);
    $action->handle($this->appointment);

    $this->appointment->refresh();

    expect($this->appointment->google_event_id)->toBe('google-event-abc123')
        ->and($this->appointment->meeting_url)->toBe('https://meet.google.com/abc-defg-hij');
});

it('saves google_event_id even when no meet link is returned', function (): void {
    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-access-token');
    $mockClient->shouldReceive('createEvent')
        ->once()
        ->andReturn(new CreateEventResponse(
            eventId: 'google-event-no-meet',
            meetLink: null,
        ));

    $action = new CreateCalendarEventAction($mockClient);
    $action->handle($this->appointment);

    $this->appointment->refresh();

    expect($this->appointment->google_event_id)->toBe('google-event-no-meet')
        ->and($this->appointment->meeting_url)->toBeNull();
});
