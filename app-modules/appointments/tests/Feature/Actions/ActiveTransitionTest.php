<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\Transitions\ActiveTransition;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Appointments\Exceptions\InvalidTransitionException;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;
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

it('sets status to Completed on progression', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
});

it('fires AppointmentCompleted event on progression', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData);

    Event::assertDispatched(AppointmentCompleted::class, fn ($e) => $e->appointment->is($appointment));
});

it('sends completed notification to user on progression', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData);

    Notification::assertNotified(
        __('appointments::resources.appointments.notifications.completed.title')
    );
});

it('queues AppointmentCompletedMail to user on progression', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData);

    Mail::assertQueued(AppointmentCompletedMail::class, fn ($m) => $m->hasTo($appointment->user->email));
});

it('sets status to Cancelled on admin cancellation', function (): void {
    $admin = User::factory()->create();
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(5)]);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::Admin,
        cancelledBy: $admin,
    ));

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('stores cancellation_actor and cancelled_by on admin cancellation', function (): void {
    $admin = User::factory()->create();
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(5)]);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::Admin,
        cancelledBy: $admin,
    ));

    $fresh = $appointment->refresh();
    expect($fresh->cancellation_actor)->toBe(CancellationActor::Admin)
        ->and($fresh->cancelled_by)->toBe($admin->id);
});

it('fires AppointmentCancelled event on admin cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(5)]);

    (new ActiveTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::Admin));

    Event::assertDispatched(AppointmentCancelled::class, fn ($e) => $e->appointment->is($appointment));
});

it('queues AppointmentCancelledMail on admin cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(5)]);

    (new ActiveTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::Admin));

    Mail::assertQueued(AppointmentCancelledMail::class, fn ($m) => $m->hasTo($appointment->user->email));
    Mail::assertNotQueued(AppointmentUserCancelledLateMail::class);
});

it('sets status to Cancelled when user cancels >= 24h before appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(25)]);
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('sets status to CancelledLate when user cancels < 24h before appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(23)]);
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate);
});

it('queues AppointmentUserCancelledLateMail on user late cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(23)]);
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    Mail::assertQueued(AppointmentUserCancelledLateMail::class, fn ($m) => $m->hasTo($appointment->user->email));
    Mail::assertNotQueued(AppointmentCancelledMail::class);
});

it('fires AppointmentCancelled event on user cancellation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(23)]);
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    ));

    Event::assertDispatched(AppointmentCancelled::class, fn ($e) => $e->appointment->is($appointment));
});

it('dispatches DeleteAppointmentCalendarEventJob when google_event_id is set', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(5), 'google_event_id' => 'evt-abc-123']);

    (new ActiveTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::Admin));

    Bus::assertDispatched(DeleteAppointmentCalendarEventJob::class, fn ($job): bool => $job->appointment->id === $appointment->id);
});

it('does not dispatch DeleteAppointmentCalendarEventJob when google_event_id is null', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours(5), 'google_event_id' => null]);

    (new ActiveTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::Admin));

    Bus::assertNotDispatched(DeleteAppointmentCalendarEventJob::class);
});

it('throws InvalidTransitionException when called on a terminal status', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();

    expect(fn () => $appointment->current_transition->handle(new TransitionData))
        ->toThrow(InvalidTransitionException::class);
});

it('throws InvalidTransitionException when user cancels a past appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);
    actingAs($appointment->user);

    expect(fn () => (new ActiveTransition($appointment))->handle(new TransitionData(
        cancellationActor: CancellationActor::User,
        cancelledBy: $appointment->user,
    )))->toThrow(InvalidTransitionException::class);
});
