<?php

declare(strict_types=1);

use App\Notifications\NotificationKind;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Appointments\Actions\Transitions\ActiveTransition;
use TresPontosTech\Appointments\Actions\Transitions\PendingTransition;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentCancelledForConsultantNotification;
use TresPontosTech\Appointments\Notifications\AppointmentCancelledNotification;
use TresPontosTech\Appointments\Notifications\AppointmentCompletedNotification;
use TresPontosTech\Appointments\Notifications\AppointmentConfirmedNotification;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Notification::fake();
    Mail::fake();
    Bus::fake();
});

it('tells the employee the appointment was confirmed', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData);

    Notification::assertSentTo(
        $appointment->user,
        AppointmentConfirmedNotification::class,
        fn (AppointmentConfirmedNotification $notification): bool => $notification->content()->appointmentId === $appointment->id
            && $notification->content()->kind === NotificationKind::AppointmentConfirmed,
    );
});

it('tells the employee the appointment was completed', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData);

    Notification::assertSentTo(
        $appointment->user,
        AppointmentCompletedNotification::class,
        fn (AppointmentCompletedNotification $notification): bool => $notification->content()->appointmentId === $appointment->id,
    );
});

it('tells the employee about a cancellation, flagging the late one', function (int $hoursAhead, NotificationKind $kind): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours($hoursAhead)]);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData(cancellationActor: CancellationActor::User));

    Notification::assertSentTo(
        $appointment->user,
        AppointmentCancelledNotification::class,
        fn (AppointmentCancelledNotification $notification): bool => $notification->content()->kind === $kind
            && $notification->content()->appointmentId === $appointment->id,
    );
})->with([
    'with notice' => [Appointment::CANCELLATION_WINDOW_HOURS + 2, NotificationKind::AppointmentCancelled],
    'inside the notice window' => [1, NotificationKind::AppointmentCancelledLate],
]);

it('tells the consultant who cancelled, outside the employee app feed', function (CancellationActor $actor, string $bodyKey): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addDays(3)]);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData(cancellationActor: $actor));

    Notification::assertSentTo(
        $appointment->consultant->user,
        AppointmentCancelledForConsultantNotification::class,
        fn (AppointmentCancelledForConsultantNotification $notification): bool => $notification->content()->kind === NotificationKind::AppointmentCancelledForConsultant
            && $notification->content()->appointmentId === $appointment->id
            && $notification->content()->body === __(sprintf('appointments::resources.appointments.notifications.%s.body', $bodyKey), ['name' => $appointment->user->name])
            && ! in_array($notification->content()->kind, NotificationKind::shownInEmployeeApp(), true),
    );
})->with([
    'by the user' => [CancellationActor::User, 'cancelled_by_user'],
    'by an admin' => [CancellationActor::Admin, 'cancelled_by_admin'],
]);
