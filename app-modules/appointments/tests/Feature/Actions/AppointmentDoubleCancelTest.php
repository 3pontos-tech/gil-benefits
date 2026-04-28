<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Exceptions\InvalidTransitionException;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Mail::fake();
    Event::fake();
    Bus::fake();
});

it('throws InvalidTransitionException when appointment is already cancelled', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create([
        'google_event_id' => 'google-event-123',
    ]);
    actingAs($appointment->user);

    expect(fn () => $appointment->current_transition->handle(new TransitionData(
        cancellationActor: CancellationActor::Admin,
    )))->toThrow(InvalidTransitionException::class);

    Mail::assertNothingQueued();
    Event::assertNotDispatched(AppointmentCancelled::class);
    Bus::assertNotDispatched(DeleteAppointmentCalendarEventJob::class);
});

it('dispatches side effects only once on double cancel', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create([
        'google_event_id' => 'google-event-123',
    ]);
    actingAs($appointment->user);

    $data = new TransitionData(cancellationActor: CancellationActor::Admin);

    $appointment->current_transition->handle($data);

    expect(fn () => $appointment->refresh()->current_transition->handle($data))
        ->toThrow(InvalidTransitionException::class);

    Bus::assertDispatchedTimes(DeleteAppointmentCalendarEventJob::class, 1);
    Event::assertDispatchedTimes(AppointmentCancelled::class, 1);
    Mail::assertQueued(AppointmentCancelledMail::class, 1);
});
