<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Actions\ConsumeCredit;
use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

beforeEach(function (): void {
    $this->holder = User::factory()->create();
    $this->company = Company::factory()->create();
});

function availableCreditFor(User $holder, Company $company, array $state = []): UserCredit
{
    return UserCredit::factory()->available()->create([
        'holder_id' => $holder->getKey(),
        'owner_id' => $holder->getKey(),
        'company_id' => $company->getKey(),
        ...$state,
    ]);
}

function consumeOneCredit(User $holder, Company $company, ?string $appointmentId = null): void
{
    resolve(ConsumeCredit::class)->execute(new CreditDTO(
        holderId: $holder->getKey(),
        companyId: $company->getKey(),
        appointmentId: $appointmentId ?? Appointment::factory()->create()->getKey(),
    ));
}

it('spends the credit that expires before the one that never does', function (): void {
    $perennial = availableCreditFor($this->holder, $this->company, [
        'expires_at' => null,
        'created_at' => now()->subMonth(),
    ]);
    $dated = availableCreditFor($this->holder, $this->company, [
        'expires_at' => now()->addWeek(),
        'created_at' => now(),
    ]);

    consumeOneCredit($this->holder, $this->company);

    expect($dated->fresh()->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($perennial->fresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('spends the nearest deadline first when two credits carry one', function (): void {
    $later = availableCreditFor($this->holder, $this->company, ['expires_at' => now()->addMonth()]);
    $sooner = availableCreditFor($this->holder, $this->company, ['expires_at' => now()->addDay()]);

    consumeOneCredit($this->holder, $this->company);

    expect($sooner->fresh()->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($later->fresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('falls back to the oldest when no credit carries a deadline', function (): void {
    $older = availableCreditFor($this->holder, $this->company, ['created_at' => now()->subMonth()]);
    $newer = availableCreditFor($this->holder, $this->company, ['created_at' => now()]);

    consumeOneCredit($this->holder, $this->company);

    expect($older->fresh()->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($newer->fresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('skips a credit whose deadline already passed', function (): void {
    $lapsed = availableCreditFor($this->holder, $this->company, ['expires_at' => now()->subDay()]);
    $good = availableCreditFor($this->holder, $this->company, ['expires_at' => null]);

    consumeOneCredit($this->holder, $this->company);

    expect($lapsed->fresh()->status)->toBe(UserCreditStatusEnum::Available)
        ->and($good->fresh()->status)->toBe(UserCreditStatusEnum::InUse);
});

it('never reaches across companies', function (): void {
    $elsewhere = availableCreditFor($this->holder, Company::factory()->create(), ['expires_at' => now()->addDay()]);
    $here = availableCreditFor($this->holder, $this->company, ['expires_at' => null]);

    consumeOneCredit($this->holder, $this->company);

    expect($elsewhere->fresh()->status)->toBe(UserCreditStatusEnum::Available)
        ->and($here->fresh()->status)->toBe(UserCreditStatusEnum::InUse);
});

it('ties the spent credit to the appointment', function (): void {
    $credit = availableCreditFor($this->holder, $this->company);
    $appointment = Appointment::factory()->create();

    consumeOneCredit($this->holder, $this->company, (string) $appointment->getKey());

    expect($credit->fresh()->appointment_id)->toBe((string) $appointment->getKey());
});
