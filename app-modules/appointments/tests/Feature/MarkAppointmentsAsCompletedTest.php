<?php

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Jobs\MarkAppointmentsAsCompleted;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

use function Pest\Laravel\assertDatabaseHas;

it('marks active appointments more than 1 day old as completed', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subDays(2)]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Completed,
    ]);
});

it('ignores active appointments within the 1 day buffer', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHours(23)]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Active,
    ]);
});

it('ignores active appointments without a consultant', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->withoutConsultant()
        ->create(['appointment_at' => now()->subDay()]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Active,
    ]);
});

it('ignores active appointments scheduled in the future', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addDay()]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Active,
    ]);
});

it('ignores appointments with non-active status', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()
        ->withStatus($status)
        ->create(['appointment_at' => now()->subDay()]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => $status,
    ]);
})->with([
    'completed' => AppointmentStatus::Completed,
    'cancelled' => AppointmentStatus::Cancelled,
    'cancelled_late' => AppointmentStatus::CancelledLate,
]);

it('cancels a pending appointment whose date passed without confirmation', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->subDays(2)]);

    (new MarkAppointmentsAsCompleted)->handle();

    // Concluir seria mentira: ninguém confirmou nem atendeu. Cancelar é o que
    // aconteceu de fato, e devolve a consulta a quem marcou.
    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Cancelled,
        'cancellation_actor' => CancellationActor::System,
    ]);
});

it('leaves a pending appointment alone while its date has not passed', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->addDay()]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Pending,
    ]);
});

it('returns the credit of a pending appointment it cancels', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['appointment_at' => now()->subDays(2)]);

    $credit = UserCredit::factory()->create([
        'owner_id' => $appointment->user_id,
        'holder_id' => $appointment->user_id,
        'company_id' => $appointment->company_id,
        'appointment_id' => $appointment->getKey(),
        'status' => UserCreditStatusEnum::InUse,
    ]);

    (new MarkAppointmentsAsCompleted)->handle();

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('consumes the credit of an active appointment it completes', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subDays(2)]);

    $credit = UserCredit::factory()->create([
        'owner_id' => $appointment->user_id,
        'holder_id' => $appointment->user_id,
        'company_id' => $appointment->company_id,
        'appointment_id' => $appointment->getKey(),
        'status' => UserCreditStatusEnum::InUse,
    ]);

    (new MarkAppointmentsAsCompleted)->handle();

    // A consultoria aconteceu: o crédito é gasto de verdade, não volta.
    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
});
