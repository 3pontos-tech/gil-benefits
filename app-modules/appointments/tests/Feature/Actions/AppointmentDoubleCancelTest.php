<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentPendingStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
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

it('does nothing when appointment is already cancelled', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create([
        'google_event_id' => 'google-event-123',
    ]);
    actingAs($appointment->user);

    (new AppointmentPendingStep($appointment))->cancel();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
    Mail::assertNothingQueued();
    Event::assertNotDispatched(AppointmentCancelled::class);
    Bus::assertNotDispatched(DeleteAppointmentCalendarEventJob::class);
});

it('dispatches side effects only once on double cancel', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create([
        'google_event_id' => 'google-event-123',
    ]);
    actingAs($appointment->user);

    $step = new AppointmentPendingStep($appointment);
    $step->cancel();
    $step->cancel();

    Bus::assertDispatchedTimes(DeleteAppointmentCalendarEventJob::class, 1);
    Event::assertDispatchedTimes(AppointmentCancelled::class, 1);
    Mail::assertQueued(AppointmentCancelledMail::class, 1);
});
