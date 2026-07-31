<?php

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\AppointmentCalendarSynchronizer;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CreateEventResponse;

/**
 * @param  array{0: string, 1: int, 2: bool}  $exceptionArgs  message, code, retryable
 */
function fakeCalendarClientThrowingOnDelete(array $exceptionArgs): void
{
    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-access-token');
    $mockClient->shouldReceive('deleteEvent')->andThrow(new GoogleCalendarApiException(...$exceptionArgs));

    app()->instance(GoogleCalendarClient::class, $mockClient);
}

function appointmentWithConsultantEvent(): array
{
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'evt-123',
    ]);

    return [$appointment, $consultant];
}

/**
 * These three guard cases used to live inline at the ViewAppointment call site. They are asserted
 * here to pin the behaviour down after it moved into the synchronizer.
 */
it('never attempts a creation when there is nothing to create for', function (Closure $makeAppointment): void {
    $appointment = $makeAppointment();

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldNotReceive('getAccessToken');
    $mockClient->shouldNotReceive('createEvent');

    app()->instance(GoogleCalendarClient::class, $mockClient);

    expect((new AppointmentCalendarSynchronizer)->placeForCurrentConsultant($appointment))->toBeTrue();
})->with([
    'appointment already has an event' => [fn (): Appointment => Appointment::factory()->create([
        'consultant_id' => Consultant::factory()->create(['email' => 'consultant@workspace.com'])->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'evt-already-there',
    ])],
    'appointment has no consultant' => [fn (): Appointment => Appointment::factory()->create([
        'consultant_id' => null,
        'status' => AppointmentStatus::Pending,
        'google_event_id' => null,
    ])],
    'consultant has no email' => [fn (): Appointment => Appointment::factory()->create([
        'consultant_id' => Consultant::factory()->create(['email' => ''])->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => null,
    ])],
]);

it('reports a failed creation to the caller even though the job swallows it', function (): void {
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => null,
    ]);

    // The creation job logs and returns for non-retryable failures, so nothing is thrown.
    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-access-token');
    $mockClient->shouldReceive('createEvent')
        ->andThrow(new GoogleCalendarApiException('notACalendarUser', 403, retryable: false));

    app()->instance(GoogleCalendarClient::class, $mockClient);

    $synced = (new AppointmentCalendarSynchronizer)->placeForCurrentConsultant($appointment);

    expect($synced)->toBeFalse()
        ->and($appointment->refresh()->google_event_id)->toBeNull();
});

it('reports a failed creation when rescheduling an appointment that has no event yet', function (): void {
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => null,
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-access-token');
    $mockClient->shouldReceive('createEvent')
        ->andThrow(new GoogleCalendarApiException('notACalendarUser', 403, retryable: false));

    app()->instance(GoogleCalendarClient::class, $mockClient);

    expect((new AppointmentCalendarSynchronizer)->reschedule($appointment))->toBeFalse();
});

it('returns true when the creation actually lands', function (): void {
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Active,
        'google_event_id' => null,
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-access-token');
    $mockClient->shouldReceive('createEvent')->once()->andReturn(
        CreateEventResponse::make(['id' => 'created-evt', 'conferenceData' => ['entryPoints' => [
            ['entryPointType' => 'video', 'uri' => 'https://meet.google.com/abc-defg-hij'],
        ]]]),
    );

    app()->instance(GoogleCalendarClient::class, $mockClient);

    expect((new AppointmentCalendarSynchronizer)->placeForCurrentConsultant($appointment))->toBeTrue()
        ->and($appointment->refresh()->google_event_id)->toBe('created-evt');
});

it('returns true and stays silent when the calendar operation succeeds', function (): void {
    Log::spy();
    Exceptions::fake();

    [$appointment, $consultant] = appointmentWithConsultantEvent();

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-access-token');
    $mockClient->shouldReceive('deleteEvent')->once();
    app()->instance(GoogleCalendarClient::class, $mockClient);

    $synced = (new AppointmentCalendarSynchronizer)->removeFrom($appointment, $consultant);

    expect($synced)->toBeTrue()
        ->and($appointment->refresh()->google_event_id)->toBeNull();

    Exceptions::assertNothingReported();
    Log::shouldNotHaveReceived('warning');
});

it('logs instead of reporting when the calendar operation is not retryable', function (): void {
    Log::spy();
    Exceptions::fake();

    [$appointment, $consultant] = appointmentWithConsultantEvent();

    fakeCalendarClientThrowingOnDelete([
        'Failed to delete event evt-123 for consultant@workspace.com: {"error":{"errors":[{"reason":"notACalendarUser"}]}}',
        403,
        false,
    ]);

    $synced = (new AppointmentCalendarSynchronizer)->removeFrom($appointment, $consultant);

    expect($synced)->toBeFalse();

    Exceptions::assertNothingReported();
    Log::shouldHaveReceived('warning')->once();
});

it('scrubs the consultant email from the skipped operation log', function (): void {
    Log::spy();
    Exceptions::fake();

    [$appointment, $consultant] = appointmentWithConsultantEvent();

    fakeCalendarClientThrowingOnDelete([
        'Failed to delete event evt-123 for consultant@workspace.com',
        403,
        false,
    ]);

    (new AppointmentCalendarSynchronizer)->removeFrom($appointment, $consultant);

    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message, array $context): bool => ! str_contains($context['reason'], 'consultant@workspace.com')
            && $context['error_code'] === 403,
    );
});

it('reports retryable calendar failures', function (): void {
    Exceptions::fake();

    [$appointment, $consultant] = appointmentWithConsultantEvent();

    fakeCalendarClientThrowingOnDelete(['Google is down', 500, true]);

    $synced = (new AppointmentCalendarSynchronizer)->removeFrom($appointment, $consultant);

    expect($synced)->toBeFalse();

    Exceptions::assertReported(GoogleCalendarApiException::class);
});

it('reports non calendar exceptions', function (): void {
    Exceptions::fake();

    [$appointment, $consultant] = appointmentWithConsultantEvent();

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andThrow(new RuntimeException('boom'));
    app()->instance(GoogleCalendarClient::class, $mockClient);

    $synced = (new AppointmentCalendarSynchronizer)->removeFrom($appointment, $consultant);

    expect($synced)->toBeFalse();

    Exceptions::assertReported(RuntimeException::class);
});
