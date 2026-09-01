<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

use function Pest\Laravel\travelTo;

/**
 * Reancora o plano contratual do empregador.
 */
function anchorContractualPlanOn(User $employee, string $anchor): void
{
    CompanyPlan::query()
        ->where('company_id', $employee->employerCompanyId())
        ->update(['starts_at' => $anchor]);

}

function bookingFor(User $employee, AppointmentStatus $status = AppointmentStatus::Pending): Appointment
{
    return Appointment::factory()
        ->withStatus($status)
        ->create([
            'user_id' => $employee->getKey(),
            'appointment_at' => now()->addDays(3),
        ]);
}

it('prefers the company plan quota over an individual subscription', function (): void {
    $employee = actingAsEmployee(); // CompanyPlan ativo: 1 agendamento/mês

    // Assinatura individual com cota MAIOR (5) — não deve prevalecer.
    $plan = Plan::factory()->createOne([
        'type' => BillableTypeEnum::User->value,
        'active' => true,
    ]);

    $price = Price::query()->create([
        'billing_plan_id' => $plan->id,
        'billing_scheme' => 'per_unit',
        'tiers_mode' => 'volume',
        'type' => 'recurring',
        'unit_amount_decimal' => 5000,
        'active' => true,
        'provider_price_id' => 'price_individual_test',
        'monthly_appointments' => 5,
        'whatsapp_enabled' => true,
        'materials_enabled' => true,
    ]);

    $employee->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_individual_123',
        'stripe_status' => 'active',
        'stripe_price' => $price->provider_price_id,
        'quantity' => 1,
    ]);

    expect($employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('does not count a booking made in the previous cycle', function (): void {
    travelTo('2026-09-09 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    bookingFor($employee);

    travelTo('2026-09-11 10:00');

    expect($employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('counts a booking made inside the current cycle', function (): void {
    travelTo('2026-09-11 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    bookingFor($employee);

    travelTo('2026-09-15 10:00');

    expect($employee->fresh()->monthly_appointments_left)->toBe(0);
});

it('never accumulates quota across untouched cycles', function (): void {
    travelTo('2026-09-11 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    // Dois ciclos inteiros sem nenhuma reserva.
    travelTo('2026-11-15 10:00');

    expect($employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('frees the quota for a cancelled booking but not for a late cancellation', function (): void {
    travelTo('2026-09-11 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    $booking = bookingFor($employee);
    expect($employee->fresh()->monthly_appointments_left)->toBe(0);

    $booking->update(['status' => AppointmentStatus::Cancelled]);
    expect($employee->fresh()->monthly_appointments_left)->toBe(1);

    $booking->update(['status' => AppointmentStatus::CancelledLate]);
    expect($employee->fresh()->monthly_appointments_left)->toBe(0);
});

it('gives a full cycle to whoever joins mid-cycle', function (): void {
    travelTo('2026-09-08 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    expect($employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('has no quota without a plan or a subscription', function (): void {
    $orphan = User::factory()->create();

    expect($orphan->monthly_appointments_left)->toBe(0);
});

it('reflects a new booking immediately, without waiting for the cache to expire', function (): void {
    travelTo('2026-09-15 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    // Popula o cache com o saldo cheio, como qualquer leitura de tela faria.
    expect($employee->fresh()->monthly_appointments_left)->toBe(1);

    bookingFor($employee);

    expect($employee->fresh()->monthly_appointments_left)->toBe(0);
});
