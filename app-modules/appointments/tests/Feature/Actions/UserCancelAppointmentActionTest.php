<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\UserCancelAppointmentAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentUserCancelledLateMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Mail::fake();
    Bus::fake();
    Event::fake();
});

// --- On-time cancellation (>= 24h) ---

it('sets status to Cancelled when >= 24h before appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(25)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('queues AppointmentCancelledMail on on-time cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(25)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Mail::assertQueued(AppointmentCancelledMail::class, fn ($mail) => $mail->hasTo($appointment->user->email));
});

it('fires AppointmentCancelled event on on-time cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(25)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Event::assertDispatched(AppointmentCancelled::class, fn ($event) => $event->appointment->is($appointment));
});

// --- Late cancellation (< 24h) ---

it('sets status to CancelledLate when < 24h before appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(23)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate);
});

it('queues AppointmentUserCancelledLateMail on late cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(23)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Mail::assertQueued(AppointmentUserCancelledLateMail::class, fn ($mail) => $mail->hasTo($appointment->user->email));
    Mail::assertNotQueued(AppointmentCancelledMail::class);
});

it('fires AppointmentCancelled event on late cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(23)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Event::assertDispatched(AppointmentCancelled::class, fn ($event) => $event->appointment->is($appointment));
});

it('sets status to CancelledLate when appointment is in the past', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate);
});

// --- Boundary ---

it('sets status to Cancelled when exactly 24h before appointment', function (): void {
    $this->travelTo(now()->startOfSecond());

    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(24)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

// --- Google Calendar ---

it('dispatches DeleteAppointmentCalendarEventJob when google_event_id is set', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create([
            'appointment_at' => now()->addHours(23),
            'google_event_id' => 'google-event-123',
        ]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Bus::assertDispatched(DeleteAppointmentCalendarEventJob::class, fn ($job) => $job->appointment->id === $appointment->id);
});

it('does not dispatch DeleteAppointmentCalendarEventJob when google_event_id is null', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create([
            'appointment_at' => now()->addHours(23),
            'google_event_id' => null,
        ]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Bus::assertNotDispatched(DeleteAppointmentCalendarEventJob::class);
});

// --- Idempotency ---

it('does nothing when appointment is already Cancelled', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Cancelled)
        ->create(['appointment_at' => now()->addHours(25)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Mail::assertNothingQueued();
    Event::assertNotDispatched(AppointmentCancelled::class);
});

it('does nothing when appointment is already Completed', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['appointment_at' => now()->addHours(25)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Mail::assertNothingQueued();
    Event::assertNotDispatched(AppointmentCancelled::class);
});

it('does nothing when appointment is already CancelledLate', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::CancelledLate)
        ->create(['appointment_at' => now()->addHours(23)]);

    actingAs($appointment->user);

    (new UserCancelAppointmentAction)->handle($appointment);

    Mail::assertNothingQueued();
    Event::assertNotDispatched(AppointmentCancelled::class);
});

// --- Ownership guard ---

it('does nothing when acting user does not own the appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(25)]);

    $otherUser = User::factory()->create();
    actingAs($otherUser);

    (new UserCancelAppointmentAction)->handle($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Pending);
    Mail::assertNothingQueued();
    Event::assertNotDispatched(AppointmentCancelled::class);
});
