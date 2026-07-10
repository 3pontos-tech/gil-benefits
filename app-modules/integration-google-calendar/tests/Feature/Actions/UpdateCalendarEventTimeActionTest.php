<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpdateCalendarEventTimeAction;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;

it('patches the existing event time keeping the same event and meeting link', function (): void {
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Active,
        'appointment_at' => Date::now()->addDays(3)->setTime(14, 0),
        'google_event_id' => 'evt-123',
        'meeting_url' => 'https://meet.google.com/keep-this',
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->with('consultant@workspace.com')
        ->andReturn('fake-access-token');

    $mockClient->shouldReceive('patchEvent')
        ->once()
        ->withArgs(function (string $token, string $calendarId, string $eventId, array $payload): bool {
            $start = Date::parse($payload['start']['dateTime']);
            $end = Date::parse($payload['end']['dateTime']);

            return $token === 'fake-access-token'
                && $calendarId === 'consultant@workspace.com'
                && $eventId === 'evt-123'
                && (int) $start->diffInMinutes($end) === 60;
        });

    (new UpdateCalendarEventTimeAction($mockClient))->handle($appointment);

    // The event and its Meet link are preserved — only the time changes.
    expect($appointment->refresh()->google_event_id)->toBe('evt-123')
        ->and($appointment->meeting_url)->toBe('https://meet.google.com/keep-this');
});

it('does nothing when the appointment has no event', function (): void {
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => null,
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldNotReceive('getAccessToken');
    $mockClient->shouldNotReceive('patchEvent');

    (new UpdateCalendarEventTimeAction($mockClient))->handle($appointment);
});
