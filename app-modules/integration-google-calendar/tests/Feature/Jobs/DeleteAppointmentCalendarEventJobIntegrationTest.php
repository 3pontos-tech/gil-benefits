<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\DeleteCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

/**
 * Exercises the whole delete path — job, action and the real GoogleCalendarClient — against faked
 * wire responses. The other job tests stub the client with a hand-built exception, which cannot
 * catch a regression in how the client itself classifies a Google error.
 */
beforeEach(function (): void {
    static $privateKey = null;

    if ($privateKey === null) {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
    }

    @mkdir(storage_path('testing'), 0755, true);
    $credPath = tempnam(storage_path('testing'), 'gc-creds-');
    file_put_contents($credPath, json_encode([
        'client_email' => 'sa@project.iam.gserviceaccount.com',
        'private_key' => $privateKey,
    ]));

    $this->credPath = $credPath;

    config(['google-calendar.service_account_credentials' => 'testing/' . basename($credPath)]);

    Http::preventStrayRequests();
});

afterEach(function (): void {
    @unlink($this->credPath);
});

function appointmentPendingDeletion(): Appointment
{
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);

    return Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Cancelled,
        'google_event_id' => 'evt-123',
    ]);
}

it('skips the deletion without failing the job when Google Calendar is disabled for the consultant', function (): void {
    Log::spy();

    $appointment = appointmentPendingDeletion();

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.token'], 200),
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'error' => [
                'errors' => [['domain' => 'calendar', 'reason' => 'notACalendarUser']],
                'code' => 403,
            ],
        ], 403),
    ]);

    // Would throw before the client learned to classify notACalendarUser as non-retryable.
    (new DeleteAppointmentCalendarEventJob($appointment))->handle(resolve(DeleteCalendarEventAction::class));

    Log::shouldHaveReceived('warning')->once();

    // Token exchange plus a single DELETE: the permanent 403 is not retried.
    Http::assertSentCount(2);

    // The event id is kept: the event is unreachable, not gone, so it must not be orphaned.
    expect($appointment->refresh()->google_event_id)->toBe('evt-123');
});

it('clears the appointment event fields when Google accepts the deletion', function (): void {
    $appointment = appointmentPendingDeletion();

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.token'], 200),
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response('', 204),
    ]);

    (new DeleteAppointmentCalendarEventJob($appointment))->handle(resolve(DeleteCalendarEventAction::class));

    expect($appointment->refresh()->google_event_id)->toBeNull()
        ->and($appointment->meeting_url)->toBeNull();
});

it('lets a transient server error fail the job so the queue retries it', function (): void {
    $appointment = appointmentPendingDeletion();

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.token'], 200),
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['error' => 'backendError'], 500),
    ]);

    expect(fn () => (new DeleteAppointmentCalendarEventJob($appointment))->handle(resolve(DeleteCalendarEventAction::class)))
        ->toThrow(GoogleCalendarApiException::class);

    expect($appointment->refresh()->google_event_id)->toBe('evt-123');
});
