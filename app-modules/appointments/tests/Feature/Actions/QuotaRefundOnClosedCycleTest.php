<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\UserCredit;

use function Pest\Laravel\travelTo;

/**
 * Cenário base de todos os testes: virada dia 10, reserva feita em 09/set (último
 * dia do ciclo que fecha), consulta marcada para 12/set.
 */
function employeeAnchoredOnDayTen(): User
{
    travelTo('2026-09-09 10:00');

    $employee = actingAsEmployee();

    CompanyPlan::query()
        ->where('company_id', $employee->employerCompanyId())
        ->update(['starts_at' => '2026-03-10']);

    return $employee;
}

function bookingOnLastDayOfCycle(User $employee): Appointment
{
    return Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $employee->getKey(),
            'appointment_at' => '2026-09-12 14:00',
        ]);
}

function cancelAs(Appointment $appointment, CancellationActor $actor, ?User $by = null): void
{
    $appointment->current_transition->handle(new TransitionData(
        cancellationActor: $actor,
        cancelledBy: $by,
    ));
}

it('refunds the quota when the debited cycle has already closed', function (): void {
    $employee = employeeAnchoredOnDayTen();
    $appointment = bookingOnLastDayOfCycle($employee);

    travelTo('2026-09-11 10:00');
    cancelAs($appointment, CancellationActor::User, $employee);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($appointment->quota_refunded_at)->not->toBeNull()
        // 1 do ciclo corrente + 1 devolvido do ciclo que fechou.
        ->and($employee->fresh()->monthly_appointments_left)->toBe(2);
});

it('does not refund when the cancellation happens inside the debited cycle', function (): void {
    $employee = employeeAnchoredOnDayTen();

    travelTo('2026-09-11 10:00');
    $appointment = bookingOnLastDayOfCycle($employee);

    travelTo('2026-09-11 14:00');
    cancelAs($appointment, CancellationActor::User, $employee);

    expect($appointment->refresh()->quota_refunded_at)->toBeNull()
        // A cota volta pela própria contagem, sem carimbo.
        ->and($employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('does not refund a late cancellation', function (): void {
    $employee = employeeAnchoredOnDayTen();
    $appointment = bookingOnLastDayOfCycle($employee);

    travelTo('2026-09-12 12:00');
    cancelAs($appointment, CancellationActor::User, $employee);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate)
        ->and($appointment->quota_refunded_at)->toBeNull()
        ->and($employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('does not refund quota for an appointment that was paid with a credit', function (): void {
    $employee = employeeAnchoredOnDayTen();
    $appointment = bookingOnLastDayOfCycle($employee);

    $credit = UserCredit::factory()->create([
        'owner_id' => $employee->getKey(),
        'holder_id' => $employee->getKey(),
        'company_id' => filament()->getTenant()->getKey(),
        'appointment_id' => $appointment->getKey(),
        'status' => UserCreditStatusEnum::InUse,
    ]);

    travelTo('2026-09-11 10:00');
    cancelAs($appointment, CancellationActor::User, $employee);

    expect($appointment->refresh()->quota_refunded_at)->toBeNull()
        ->and($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available)
        ->and($employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('refunds an admin cancellation made after the turn', function (): void {
    $employee = employeeAnchoredOnDayTen();
    $appointment = bookingOnLastDayOfCycle($employee);

    travelTo('2026-09-11 10:00');
    cancelAs($appointment, CancellationActor::Admin);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($appointment->quota_refunded_at)->not->toBeNull()
        ->and($employee->fresh()->monthly_appointments_left)->toBe(2);
});

it('expires the refund when the cycle it was granted in turns', function (): void {
    $employee = employeeAnchoredOnDayTen();
    $appointment = bookingOnLastDayOfCycle($employee);

    travelTo('2026-09-11 10:00');
    cancelAs($appointment, CancellationActor::User, $employee);

    travelTo('2026-10-10 00:00');

    // O carimbo de 11/set não cai mais na janela corrente: some sozinho.
    expect($employee->fresh()->monthly_appointments_left)->toBe(1);
});
