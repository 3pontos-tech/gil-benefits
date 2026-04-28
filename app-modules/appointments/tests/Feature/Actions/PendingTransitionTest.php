<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\Transitions\PendingTransition;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Events\AppointmentBooked;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Exceptions\InvalidTransitionException;
use TresPontosTech\Appointments\Exceptions\MissingTransitionDataException;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
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

it('sets status to Active on progression', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Active);
});

it('fires AppointmentBooked event on progression', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData);

    Event::assertDispatched(AppointmentBooked::class, fn ($e) => $e->appointment->is($appointment));
});

it('sends scheduled notification to user on progression', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData);

    Notification::assertNotified(
        __('appointments::resources.appointments.notifications.scheduled.title')
    );
});

it('queues AppointmentScheduledMail to consultant on progression', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData);

    Mail::assertQueued(AppointmentScheduledMail::class, fn ($m) => $m->hasTo($appointment->consultant->email));
});

it('throws MissingTransitionDataException when no consultant on progression', function (): void {
    $appointment = Appointment::factory()
        ->withoutConsultant()
        ->withStatus(AppointmentStatus::Pending)
        ->create();
    actingAs($appointment->user);

    expect(fn () => (new PendingTransition($appointment))->handle(new TransitionData))
        ->toThrow(MissingTransitionDataException::class);
});

it('sets status to Cancelled on admin cancellation', function (): void {
    $admin = User::factory()->create();
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(5)]);

    (new PendingTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::Admin,
        cancelledBy: $admin,
    ));

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('stores cancellation_actor and cancelled_by on admin cancellation', function (): void {
    $admin = User::factory()->create();
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(5)]);

    (new PendingTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::Admin,
        cancelledBy: $admin,
    ));

    $fresh = $appointment->refresh();
    expect($fresh->cancellation_actor)->toBe(CancellationActor::Admin)
        ->and($fresh->cancelled_by)->toBe($admin->id);
});

it('fires AppointmentCancelled event on admin cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(5)]);

    (new PendingTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::Admin));

    Event::assertDispatched(AppointmentCancelled::class, fn ($e) => $e->appointment->is($appointment));
});

it('queues AppointmentCancelledMail on admin cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(5)]);

    (new PendingTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::Admin));

    Mail::assertQueued(AppointmentCancelledMail::class, fn ($m) => $m->hasTo($appointment->user->email));
    Mail::assertNotQueued(AppointmentUserCancelledLateMail::class);
});

it('sets status to Cancelled when user cancels >= 24h before appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(25)]);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('queues AppointmentCancelledMail on user on-time cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(25)]);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    Mail::assertQueued(AppointmentCancelledMail::class, fn ($m) => $m->hasTo($appointment->user->email));
    Mail::assertNotQueued(AppointmentUserCancelledLateMail::class);
});

it('sets status to CancelledLate when user cancels < 24h before appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(23)]);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate);
});

it('queues AppointmentUserCancelledLateMail on user late cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(23)]);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    Mail::assertQueued(AppointmentUserCancelledLateMail::class, fn ($m) => $m->hasTo($appointment->user->email));
    Mail::assertNotQueued(AppointmentCancelledMail::class);
});

it('fires AppointmentCancelled event on user late cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(23)]);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    Event::assertDispatched(AppointmentCancelled::class, fn ($e) => $e->appointment->is($appointment));
});

it('dispatches DeleteAppointmentCalendarEventJob when google_event_id is set', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(5), 'google_event_id' => 'evt-123']);

    (new PendingTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::Admin));

    Bus::assertDispatched(DeleteAppointmentCalendarEventJob::class, fn ($job): bool => $job->appointment->id === $appointment->id);
});

it('does not dispatch DeleteAppointmentCalendarEventJob when google_event_id is null', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours(5), 'google_event_id' => null]);

    (new PendingTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::Admin));

    Bus::assertNotDispatched(DeleteAppointmentCalendarEventJob::class);
});

it('throws InvalidTransitionException when called on a terminal status', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create();

    expect(fn () => $appointment->current_transition->handle(new TransitionData))
        ->toThrow(InvalidTransitionException::class);
});
