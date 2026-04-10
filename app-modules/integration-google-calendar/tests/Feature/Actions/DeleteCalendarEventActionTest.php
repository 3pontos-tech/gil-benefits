<?php

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\DeleteCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;

it('deletes google calendar event and clears appointment fields', function (): void {
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'google-event-to-delete',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->with('consultant@workspace.com')
        ->andReturn('fake-access-token');

    $mockClient->shouldReceive('deleteEvent')
        ->once()
        ->with('fake-access-token', 'consultant@workspace.com', 'google-event-to-delete');

    $action = new DeleteCalendarEventAction($mockClient);
    $action->handle($appointment);

    $appointment->refresh();

    expect($appointment->google_event_id)->toBeNull()
        ->and($appointment->meeting_url)->toBeNull();
});

it('does nothing when google_event_id is null', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Active,
        'google_event_id' => null,
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldNotReceive('getAccessToken');
    $mockClient->shouldNotReceive('deleteEvent');

    $action = new DeleteCalendarEventAction($mockClient);
    $action->handle($appointment);
});

it('does nothing when consultant has no email', function (): void {
    $consultant = Consultant::factory()->create(['email' => '']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'google-event-123',
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldNotReceive('getAccessToken');
    $mockClient->shouldNotReceive('deleteEvent');

    $action = new DeleteCalendarEventAction($mockClient);
    $action->handle($appointment);
});
