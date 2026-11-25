<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\CheckImportSeatLimitAction;

function companyWithSeats(int $seats, int $activeEmployees): Company
{
    $company = Company::factory()->create();

    User::factory()->count($activeEmployees)->create()
        ->each(fn (User $user) => $company->employees()->attach($user->getKey(), ['active' => true]));

    CompanyPlan::query()->create([
        'company_id' => $company->id,
        'plan_id' => Plan::factory()->create(['active' => true])->id,
        'status' => CompanyPlanStatusEnum::Active->value,
        'monthly_appointments_per_employee' => 1,
        'starts_at' => now()->subDay(),
        'seats' => $seats,
    ]);

    return $company;
}

it('allows exactly the free seats', function (): void {
    $company = companyWithSeats(seats: 3, activeEmployees: 1);

    expect(resolve(CheckImportSeatLimitAction::class)->execute($company, 2))->toBeNull();
});

it('reports the free seats and the people joining when the limit is exceeded', function (): void {
    $company = companyWithSeats(seats: 3, activeEmployees: 1);

    $error = resolve(CheckImportSeatLimitAction::class)->execute($company, 3);

    expect($error?->message)->toBe('Limite de assentos excedido. Disponíveis: 2, solicitados: 3.');
});

it('does not limit a company without a contractual plan or an active subscription', function (): void {
    $company = Company::factory()->create();

    expect(resolve(CheckImportSeatLimitAction::class)->execute($company, 500))->toBeNull();
});
