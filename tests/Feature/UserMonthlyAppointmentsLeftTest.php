<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;

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

/**
 * Reserva pela mesma empresa que `BookAppointmentAction` gravaria — nula para quem
 * assina por conta própria. Sem isso a factory inventa uma empresa por agendamento e
 * a contagem, que é escopada por empresa, nunca os encontra.
 */
function bookingFor(User $employee, AppointmentStatus $status = AppointmentStatus::Pending): Appointment
{
    return Appointment::factory()
        ->withStatus($status)
        ->create([
            'user_id' => $employee->getKey(),
            'company_id' => $employee->employerCompanyId(),
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

it('counts a no-show against the cycle, and never refunds it', function (): void {
    travelTo('2026-09-11 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    $booking = bookingFor($employee);

    // US-385: faltar sem avisar consome a consulta. Só `cancelled` sai da contagem.
    $booking->update(['status' => AppointmentStatus::NoShow]);

    travelTo('2026-09-15 10:00');

    expect($employee->fresh()->monthly_appointments_left)->toBe(0)
        ->and($booking->refresh()->quota_refunded_at)->toBeNull();
});

it('renews every employee of a company on the same anchor day', function (): void {
    travelTo('2026-09-09 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    $company = CompanyPlan::query()->firstOrFail()->company;
    $colleague = User::factory()->employee()->create();
    $company->employees()->attach($colleague->getKey());

    // Um gasta a consulta no ciclo que fecha em 09/set; o outro não.
    bookingFor($employee);

    expect($employee->fresh()->monthly_appointments_left)->toBe(0)
        ->and($colleague->fresh()->monthly_appointments_left)->toBe(1);

    // US-372: a virada é a do contrato da empresa, não a de cada um.
    travelTo('2026-09-10 00:00');

    expect($employee->fresh()->monthly_appointments_left)->toBe(1)
        ->and($colleague->fresh()->monthly_appointments_left)->toBe(1);
});

/**
 * Assinante individual próprio, ancorado no instante da chamada.
 *
 * Local porque `actingAsSubscribedEmployee()` fixa `stripe_id` e `provider_price_id`
 * no código e não pode ser chamado duas vezes na mesma execução.
 */
function subscriberAnchoredNow(string $suffix): User
{
    $user = User::factory()->employee()->create();

    $plan = Plan::factory()->createOne(['type' => BillableTypeEnum::User->value, 'active' => true]);

    $price = Price::query()->create([
        'billing_plan_id' => $plan->id,
        'billing_scheme' => 'per_unit',
        'tiers_mode' => 'volume',
        'type' => 'recurring',
        'unit_amount_decimal' => 5000,
        'active' => true,
        'provider_price_id' => 'price_' . $suffix,
        'monthly_appointments' => 1,
        'whatsapp_enabled' => true,
        'materials_enabled' => true,
    ]);

    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_' . $suffix,
        'stripe_status' => 'active',
        'stripe_price' => $price->provider_price_id,
        'quantity' => 1,
    ]);

    return $user;
}

it('keeps individual subscription cycles independent from each other', function (): void {
    travelTo('2026-04-10 09:00');
    $early = subscriberAnchoredNow('early');

    travelTo('2026-04-22 09:00');
    $late = subscriberAnchoredNow('late');

    bookingFor($early);
    bookingFor($late);

    // US-373: cada assinante vira no seu próprio dia. Em 10/mai o ciclo do
    // primeiro reabre; o do segundo só em 22/mai.
    travelTo('2026-05-10 10:00');

    expect($early->fresh()->monthly_appointments_left)->toBe(1)
        ->and($late->fresh()->monthly_appointments_left)->toBe(0);

    travelTo('2026-05-22 10:00');

    expect($late->fresh()->monthly_appointments_left)->toBe(1);
});

/**
 * Emprega a mesma pessoa numa segunda empresa, também com plano contratual ativo.
 */
function secondEmployerFor(User $employee, string $anchor): Company
{
    $company = Company::factory()->create();
    $company->employees()->attach($employee->getKey());

    CompanyPlan::query()->create([
        'company_id' => $company->getKey(),
        'plan_id' => Plan::factory()->createOne(['active' => true])->getKey(),
        'status' => 'active',
        'monthly_appointments_per_employee' => 1,
        'starts_at' => $anchor,
        'seats' => 10,
    ]);

    return $company;
}

it('does not spend the quota of one company on a booking made at another', function (): void {
    travelTo('2026-09-11 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    $firstEmployer = filament()->getTenant();
    $secondEmployer = secondEmployerFor($employee, '2026-03-10');

    // A reserva nasce sob a segunda empresa, como BookAppointmentAction gravaria.
    Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $employee->getKey(),
            'company_id' => $secondEmployer->getKey(),
            'appointment_at' => now()->addDays(3),
        ]);

    expect($employee->fresh()->monthly_appointments_left)->toBe(1);

    filament()->setTenant($secondEmployer);
    expect($employee->fresh()->monthly_appointments_left)->toBe(0);

    filament()->setTenant($firstEmployer);
    expect($employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('keeps a refund stamped at one company out of the other company balance', function (): void {
    travelTo('2026-09-11 10:00');
    $employee = actingAsEmployee();
    anchorContractualPlanOn($employee, '2026-03-10');

    $firstEmployer = filament()->getTenant();
    $secondEmployer = secondEmployerFor($employee, '2026-03-10');

    Appointment::factory()
        ->withStatus(AppointmentStatus::Cancelled)
        ->create([
            'user_id' => $employee->getKey(),
            'company_id' => $secondEmployer->getKey(),
            'appointment_at' => now()->addDays(3),
            'quota_refunded_at' => now(),
        ]);

    expect($employee->fresh()->monthly_appointments_left)->toBe(1);

    filament()->setTenant($secondEmployer);
    expect($employee->fresh()->monthly_appointments_left)->toBe(2);

    filament()->setTenant($firstEmployer);
})->group('quota');
