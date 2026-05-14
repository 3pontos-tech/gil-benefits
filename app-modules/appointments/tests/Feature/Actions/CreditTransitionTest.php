<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\Transitions\ActiveTransition;
use TresPontosTech\Appointments\Actions\Transitions\PendingTransition;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Mail::fake();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function activeAppointmentWithCredit(int $hoursUntil = 48): array
{
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHours($hoursUntil)]);

    $credit = UserCredit::factory()->inUse()->create([
        'holder_id' => $appointment->user->getKey(),
        'appointment_id' => $appointment->getKey(),
    ]);

    return [$appointment, $credit];
}

function pendingAppointmentWithCredit(int $hoursUntil = 48): array
{
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addHours($hoursUntil)]);

    $credit = UserCredit::factory()->inUse()->create([
        'holder_id' => $appointment->user->getKey(),
        'appointment_id' => $appointment->getKey(),
    ]);

    return [$appointment, $credit];
}

// ---------------------------------------------------------------------------
// Completion (Active → Completed)
// ---------------------------------------------------------------------------

describe('ActiveTransition — completion', function (): void {

    it('marks the credit as Used when the appointment is completed', function (): void {
        [$appointment, $credit] = activeAppointmentWithCredit();
        actingAs($appointment->user);

        (new ActiveTransition($appointment))->handle(new TransitionData);

        expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
    });

    it('does not affect appointments that have no credit on completion', function (): void {
        $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
        actingAs($appointment->user);

        expect(fn () => (new ActiveTransition($appointment))->handle(new TransitionData))
            ->not->toThrow(Throwable::class);
    });
});

// ---------------------------------------------------------------------------
// On-time cancellation (>= 24 h) — credit returns
// ---------------------------------------------------------------------------

describe('ActiveTransition — on-time cancellation', function (): void {

    it('returns the credit to Available when user cancels on time', function (): void {
        [$appointment, $credit] = activeAppointmentWithCredit(hoursUntil: 25);
        actingAs($appointment->user);

        (new ActiveTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::User,
            cancelledBy: $appointment->user,
        ));

        $updated = $credit->refresh();
        expect($updated->status)->toBe(UserCreditStatusEnum::Available)
            ->and($updated->appointment_id)->toBeNull();
    });

    it('returns the credit to Available when admin cancels (regardless of timing)', function (): void {
        [$appointment, $credit] = activeAppointmentWithCredit(hoursUntil: 2);

        (new ActiveTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::Admin,
        ));

        $updated = $credit->refresh();
        expect($updated->status)->toBe(UserCreditStatusEnum::Available)
            ->and($updated->appointment_id)->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// Late cancellation (< 24 h) — credit is consumed
// ---------------------------------------------------------------------------

describe('ActiveTransition — late cancellation', function (): void {

    it('keeps the credit as Used when user cancels late', function (): void {
        [$appointment, $credit] = activeAppointmentWithCredit(hoursUntil: 23);
        actingAs($appointment->user);

        (new ActiveTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::User,
            cancelledBy: $appointment->user,
        ));

        expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
    });

    it('appointment status is CancelledLate on late user cancellation', function (): void {
        [$appointment] = activeAppointmentWithCredit(hoursUntil: 23);
        actingAs($appointment->user);

        (new ActiveTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::User,
            cancelledBy: $appointment->user,
        ));

        expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate);
    });
});

// ---------------------------------------------------------------------------
// PendingTransition — same credit lifecycle for cancellations
// ---------------------------------------------------------------------------

describe('PendingTransition — on-time cancellation', function (): void {

    it('returns the credit to Available when user cancels a pending appointment on time', function (): void {
        [$appointment, $credit] = pendingAppointmentWithCredit(hoursUntil: 25);
        actingAs($appointment->user);

        (new PendingTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::User,
            cancelledBy: $appointment->user,
        ));

        $updated = $credit->refresh();
        expect($updated->status)->toBe(UserCreditStatusEnum::Available)
            ->and($updated->appointment_id)->toBeNull();
    });

    it('returns the credit to Available when admin cancels a pending appointment', function (): void {
        [$appointment, $credit] = pendingAppointmentWithCredit(hoursUntil: 2);

        (new PendingTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::Admin,
        ));

        $updated = $credit->refresh();
        expect($updated->status)->toBe(UserCreditStatusEnum::Available)
            ->and($updated->appointment_id)->toBeNull();
    });
});

describe('PendingTransition — late cancellation', function (): void {

    it('keeps the credit as Used when user cancels a pending appointment late', function (): void {
        [$appointment, $credit] = pendingAppointmentWithCredit(hoursUntil: 23);
        actingAs($appointment->user);

        (new PendingTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::User,
            cancelledBy: $appointment->user,
        ));

        expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
    });
});

// ---------------------------------------------------------------------------
// No-credit edge cases
// ---------------------------------------------------------------------------

describe('cancellation without a credit', function (): void {

    it('does not fail when a cancelled appointment has no credit', function (): void {
        $appointment = Appointment::factory()
            ->withStatus(AppointmentStatus::Active)
            ->create(['appointment_at' => now()->addHours(25)]);
        actingAs($appointment->user);

        expect(fn () => (new ActiveTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::User,
            cancelledBy: $appointment->user,
        )))->not->toThrow(Throwable::class);
    });

    it('does not affect a credit that is already Used', function (): void {
        $appointment = Appointment::factory()
            ->withStatus(AppointmentStatus::Active)
            ->create(['appointment_at' => now()->addHours(25)]);

        $credit = UserCredit::factory()->used()->create([
            'holder_id' => $appointment->user->getKey(),
            'appointment_id' => $appointment->getKey(),
        ]);

        actingAs($appointment->user);

        (new ActiveTransition($appointment))->handle(new TransitionData(
            cancellationActor: CancellationActor::User,
            cancelledBy: $appointment->user,
        ));

        // Status is already Used (not InUse) — the guard in cancelProcessStep skips it
        expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
    });
});
