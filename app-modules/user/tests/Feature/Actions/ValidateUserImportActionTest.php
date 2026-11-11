<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
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

it('accepts a department that matches ignoring case, accents and extra spaces', function (string $department): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => 'Recursos Humanos e Benefícios']);

    $rows = collect([makeValidationRow(['department' => $department])]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->toBeEmpty();
})->with([
    'exact' => 'Recursos Humanos e Benefícios',
    'upper case without accents' => 'RECURSOS HUMANOS E BENEFICIOS',
    'extra spaces' => '  recursos   humanos e benefícios ',
]);

it('accepts rows without a department', function (): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => 'Engenharia']);

    $rows = collect([
        makeValidationRow(['department' => '']),
        makeValidationRow(),
    ]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->toBeEmpty();
});

it('points each row with an unknown department and lists the company departments once at the top', function (): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => 'Vendas']);
    Department::factory()->for($company)->create(['name' => 'Vendas Internacionais']);

    $rows = collect([
        makeValidationRow(['department' => 'Vendas Inter', '__row_number' => 7]),
        makeValidationRow(['department' => 'Comercial', '__row_number' => 9]),
    ]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->toHaveCount(3)
        ->and($errors[0]->row)->toBe(0)
        ->and($errors[0]->message)->toBe('Departamentos cadastrados na empresa: Vendas, Vendas Internacionais.')
        ->and($errors[1]->row)->toBe(7)
        ->and($errors[1]->message)->toBe("Departamento 'Vendas Inter' não encontrado.")
        ->and($errors[2]->row)->toBe(9)
        ->and($errors[2]->message)->toBe("Departamento 'Comercial' não encontrado.");
});

it('tells the company has no departments when a row names one', function (): void {
    $company = Company::factory()->create();

    $rows = collect([makeValidationRow(['department' => 'Financeiro', '__row_number' => 4])]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->toHaveCount(2)
        ->and($errors[0]->message)->toStartWith('A empresa não tem departamentos cadastrados.')
        ->and($errors[1]->row)->toBe(4)
        ->and($errors[1]->message)->toBe("Departamento 'Financeiro' não encontrado.");
});

it('returns an error when the department matches more than one department', function (): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => 'Engenharia']);
    Department::factory()->for($company)->create(['name' => 'ENGENHARIA']);

    $rows = collect([makeValidationRow(['department' => 'engenharia'])]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect($errors)->toHaveCount(1)
        ->and($errors[0]->message)->toContain('corresponde a mais de um departamento');
});

it('does not match departments from another company or deleted departments', function (): void {
    $company = Company::factory()->create();
    Department::factory()->create(['name' => 'Financeiro']);
    Department::factory()->for($company)->create(['name' => 'Jurídico'])->delete();

    $rows = collect([
        makeValidationRow(['department' => 'Financeiro']),
        makeValidationRow(['department' => 'Jurídico']),
    ]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect(collect($errors)->where('row', '>', 0)->pluck('message')->all())->toBe([
        "Departamento 'Financeiro' não encontrado.",
        "Departamento 'Jurídico' não encontrado.",
    ]);
});

it('keeps non-latin and emoji names distinct instead of collapsing them', function (string $existing, string $typed): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => $existing]);

    $rows = collect([makeValidationRow(['department' => $typed, '__row_number' => 5])]);

    $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

    expect(collect($errors)->firstWhere('row', 5)?->message)->toBe(sprintf("Departamento '%s' não encontrado.", $typed));
})->with([
    'chinese' => ['销售', '市场'],
    'emoji' => ['🚀', '🎉'],
    'japanese against emoji' => ['営業', '😀'],
    'emoji suffix' => ['Vendas 🚀', 'Vendas'],
]);

it('matches fullwidth and non-latin names written the same way', function (string $existing, string $typed): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => $existing]);

    $rows = collect([makeValidationRow(['department' => $typed])]);

    expect(resolve(ValidateUserImportAction::class)->execute($rows, $company))->toBeEmpty();
})->with([
    'fullwidth' => ['Abc', 'Ａｂｃ'],
    'chinese' => ['销售', '销售'],
    'emoji' => ['Vendas 🚀', 'vendas 🚀'],
]);

it('treats a cell with only invisible spaces as no department', function (string $invisible): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => '🚀']);

    $rows = collect([makeValidationRow(['department' => $invisible])]);

    expect(resolve(ValidateUserImportAction::class)->execute($rows, $company))->toBeEmpty();
})->with([
    'non-breaking space' => ["\u{00A0}"],
    'zero-width space' => ["\u{200B}"],
]);
