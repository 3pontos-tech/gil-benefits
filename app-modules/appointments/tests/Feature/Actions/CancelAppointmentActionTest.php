<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\CancelAppointmentAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Bus::fake();
    Mail::fake();
    Event::fake([AppointmentCancelled::class]);
});

it('updates status to Cancelled', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    resolve(CancelAppointmentAction::class)->handle($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('sends in-app notification to user', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    resolve(CancelAppointmentAction::class)->handle($appointment);

    Notification::assertNotified(__('appointments::resources.appointments.notifications.cancelled.title'));
});

it('queues cancellation mail to user', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    resolve(CancelAppointmentAction::class)->handle($appointment);

    Mail::assertQueued(AppointmentCancelledMail::class, fn ($mail) => $mail->hasTo($appointment->user->email));
});

it('fires AppointmentCancelled event', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($appointment->user);

    resolve(CancelAppointmentAction::class)->handle($appointment);

    Event::assertDispatched(AppointmentCancelled::class, fn ($event): bool => $event->appointment->id === $appointment->id);
});

it('dispatches DeleteAppointmentCalendarEventJob when google_event_id is present', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'evt-abc-123',
    ]);
    actingAs($appointment->user);

    resolve(CancelAppointmentAction::class)->handle($appointment);

    Bus::assertDispatched(DeleteAppointmentCalendarEventJob::class, fn ($job): bool => $job->appointment->id === $appointment->id);
});

it('does not dispatch calendar job when google_event_id is null', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Active,
        'google_event_id' => null,
    ]);
    actingAs($appointment->user);

    resolve(CancelAppointmentAction::class)->handle($appointment);

    Bus::assertNotDispatched(DeleteAppointmentCalendarEventJob::class);
});

it('is idempotent when appointment is already Cancelled', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create();
    actingAs($appointment->user);

    resolve(CancelAppointmentAction::class)->handle($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
    Mail::assertNotQueued(AppointmentCancelledMail::class);
});

it('is idempotent when appointment is already Completed', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();
    actingAs($appointment->user);

    resolve(CancelAppointmentAction::class)->handle($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
    Mail::assertNotQueued(AppointmentCancelledMail::class);
});
