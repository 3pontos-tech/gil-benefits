<?php

declare(strict_types=1);

use App\Notifications\ContentNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Appointments\Actions\SyncAppointmentScheduleAction;
use TresPontosTech\Appointments\Actions\Transitions\PendingTransition;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Jobs\SendAppointmentRemindersJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentReminderNotification;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Notification::fake();
    Mail::fake();
    Bus::fake();
    $this->travelTo('2026-09-10 09:00:00');
});

it('reminds a confirmed appointment that starts within the hour, once, on the phone only', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create(['appointment_at' => '2026-09-10 09:50:00']);

    (new SendAppointmentRemindersJob)->handle();
    (new SendAppointmentRemindersJob)->handle();

    Notification::assertSentToTimes($appointment->user, AppointmentReminderNotification::class, 1);
    Notification::assertSentTo($appointment->user, AppointmentReminderNotification::class, fn (AppointmentReminderNotification $notification): bool => $notification->via($appointment->user) === [ContentNotification::PUSH_CHANNEL]
        && $notification->content()->push?->body === 'Hoje, às 09:50. A sala abre 15 minutos antes, pelo app.');
    expect($appointment->refresh()->reminder_sent_at)->not->toBeNull();
});

it('leaves alone what is not a confirmed appointment in the next hour', function (array $attributes, AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create($attributes);

    (new SendAppointmentRemindersJob)->handle();

    Notification::assertNotSentTo($appointment->user, AppointmentReminderNotification::class);
})->with([
    'later than the hour' => [['appointment_at' => '2026-09-10 10:30:00'], AppointmentStatus::Active],
    'already started' => [['appointment_at' => '2026-09-10 08:55:00'], AppointmentStatus::Active],
    'already reminded' => [['appointment_at' => '2026-09-10 09:40:00', 'reminder_sent_at' => '2026-09-10 08:45:00'], AppointmentStatus::Active],
    'waiting for confirmation' => [['appointment_at' => '2026-09-10 09:40:00', 'consultant_id' => null], AppointmentStatus::Pending],
]);

it('counts a confirmation within the hour as the reminder', function (string $startsAt, bool $reminded): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create(['appointment_at' => $startsAt]);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData);

    expect($appointment->refresh()->reminder_sent_at !== null)->toBe($reminded);
})->with([
    'confirmed 40 minutes before' => ['2026-09-10 09:40:00', true],
    'confirmed the day before' => ['2026-09-11 09:40:00', false],
]);

it('reminds a moved appointment again at its new time', function (): void {
    actingAsAdmin();
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create([
        'appointment_at' => '2026-09-10 09:40:00',
        'reminder_sent_at' => '2026-09-10 08:45:00',
    ]);
    $previousConsultantId = $appointment->consultant_id;
    $previousAt = $appointment->appointment_at->copy();

    $appointment->update(['appointment_at' => '2026-09-11 15:00:00', 'consultant_id' => null]);
    resolve(SyncAppointmentScheduleAction::class)->handle($appointment, $previousConsultantId, $previousAt, AppointmentHistoryActor::Admin);

    expect($appointment->refresh()->reminder_sent_at)->toBeNull();
});
