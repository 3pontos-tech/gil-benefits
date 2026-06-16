<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\ValidateUserImportAction;

function makeValidationRow(array $overrides = []): array
{
    static $counter = 0;
    ++$counter;

    return array_merge([
        'name' => 'User ' . $counter,
        'email' => 'user' . $counter . '@example.com',
        'document_id' => '1234567' . $counter,
        'tax_id' => str_pad((string) ($counter * 100000000), 11, '0', STR_PAD_LEFT),
        'phone_number' => '1199999' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
        '__row_number' => $counter + 1,
    ], $overrides);
}

it('returns an empty array when all data is valid', function (): void {
    $company = Company::factory()->create();
    $rows = collect([makeValidationRow()]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->toBeEmpty();
});

it('returns an error when required columns are missing', function (): void {
    $company = Company::factory()->create();
    $rows = collect([['name' => 'João', '__row_number' => 2]]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->not->toBeEmpty()
        ->and($errors[0]->message)->toContain('Colunas ausentes');
});

it('returns an error for a tax_id with fewer than 11 digits', function (): void {
    $company = Company::factory()->create();
    $rows = collect([makeValidationRow(['tax_id' => '123456'])]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->not->toBeEmpty()
        ->and($errors[0]->message)->toContain('tax_id');
});

it('returns an error for a phone_number with fewer than 10 digits', function (): void {
    $company = Company::factory()->create();
    $rows = collect([makeValidationRow(['phone_number' => '12345'])]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->not->toBeEmpty()
        ->and($errors[0]->message)->toContain('phone_number');
});

it('returns an error for duplicate emails in the spreadsheet', function (): void {
    $company = Company::factory()->create();
    $rows = collect([
        makeValidationRow(['email' => 'same@example.com']),
        makeValidationRow(['email' => 'same@example.com']),
    ]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->not->toBeEmpty()
        ->and(collect($errors)->first(fn ($e): bool => str_contains($e->message, 'duplicado'))->message)
        ->toContain('duplicado');
});

it('returns an error for a tax_id already registered in the system', function (): void {
    $company = Company::factory()->create();
    $existingUser = User::factory()->create();
    Detail::factory()->for($existingUser)->create(['tax_id' => '12345678901']);

    $rows = collect([makeValidationRow(['tax_id' => '12345678901'])]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->not->toBeEmpty()
        ->and($errors[0]->message)->toContain('já cadastrado');
});

it('returns an error when the seat limit is exceeded', function (): void {
    $company = Company::factory()->create();

    $existingEmployee = User::factory()->create();
    $company->employees()->attach($existingEmployee->getKey(), ['active' => true]);

    $plan = Plan::factory()->create(['active' => true]);
    CompanyPlan::query()->create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => CompanyPlanStatusEnum::Active->value,
        'monthly_appointments_per_employee' => 1,
        'starts_at' => now()->subDay(),
        'seats' => 1,
    ]);

    $rows = collect([makeValidationRow()]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->not->toBeEmpty()
        ->and($errors[0]->message)->toContain('Limite de assentos');
});
