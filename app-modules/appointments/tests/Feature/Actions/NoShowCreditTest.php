<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\Transitions\ActiveTransition;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Events\AppointmentCreditUsed;
use TresPontosTech\Credits\Models\UserCredit;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
    LaravelNotification::fake();
    Mail::fake();
    Bus::fake();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function noShowAppointmentWithCredit(): array
{
    $consultant = Consultant::factory()->create();

    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['consultant_id' => $consultant->getKey(), 'appointment_at' => now()->subHour()]);

    $credit = UserCredit::factory()->inUse()->create([
        'holder_id' => $appointment->user->getKey(),
        'appointment_id' => $appointment->getKey(),
    ]);

    return [$appointment, $credit, $consultant];
}

// ---------------------------------------------------------------------------
// Audit trail (common to every credit rule option)
// ---------------------------------------------------------------------------

it('writes an audit history row with no_show origin and credit impact', function (): void {
    [$appointment, $credit, $consultant] = noShowAppointmentWithCredit();
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        noShowMarkedBy: $consultant->user,
    ));

    $history = AppointmentHistory::query()
        ->where('appointment_id', $appointment->getKey())
        ->where('action_type', AppointmentHistoryActionType::NoShowMarked)
        ->firstOrFail();

    expect($history->new_values)->toMatchArray([
        'status' => AppointmentStatus::NoShow->value,
        'credit_impact' => 'consumed',
    ])
        ->and($history->actor_type)->toBe(AppointmentHistoryActor::Consultant)
        ->and($history->actor_id)->toBe($consultant->user->getKey());

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
});

it('records credit impact as none when the appointment has no credit', function (): void {
    $consultant = Consultant::factory()->create();
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['consultant_id' => $consultant->getKey(), 'appointment_at' => now()->subHour()]);
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        noShowMarkedBy: $consultant->user,
    ));

    $history = AppointmentHistory::query()
        ->where('appointment_id', $appointment->getKey())
        ->where('action_type', AppointmentHistoryActionType::NoShowMarked)
        ->firstOrFail();

    expect($history->new_values['credit_impact'])->toBe('none')
        ->and($history->actor_id)->toBe($consultant->user->getKey())
        ->and(UserCredit::query()->where('appointment_id', $appointment->getKey())->exists())->toBeFalse();
});

it('rolls back the status, credit and history when a failure happens inside the transaction', function (): void {
    [$appointment, $credit, $consultant] = noShowAppointmentWithCredit();
    actingAs($appointment->user);

    Event::listen(AppointmentCreditUsed::class, function (): void {
        throw new RuntimeException('boom');
    });

    expect(fn () => (new ActiveTransition($appointment))->handle(new TransitionData(
        noShowMarkedBy: $consultant->user,
    )))->toThrow(RuntimeException::class);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Active)
        ->and($credit->refresh()->status)->toBe(UserCreditStatusEnum::InUse);

    assertDatabaseMissing(AppointmentHistory::class, [
        'appointment_id' => $appointment->getKey(),
        'action_type' => AppointmentHistoryActionType::NoShowMarked->value,
    ]);
});

// ---------------------------------------------------------------------------
// Option A — credit consumed, same as a completed appointment
// ---------------------------------------------------------------------------

it('marks the credit as Used when the appointment is a no-show', function (): void {
    [$appointment, $credit, $consultant] = noShowAppointmentWithCredit();
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        noShowMarkedBy: $consultant->user,
    ));

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
});
