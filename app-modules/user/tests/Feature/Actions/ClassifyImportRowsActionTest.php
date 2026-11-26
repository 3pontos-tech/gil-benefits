<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\ClassifyImportRowsAction;
use TresPontosTech\User\DTOs\ClassifiedImportRowDTO;
use TresPontosTech\User\Enums\ImportRowOutcome;

function classifyImportRow(string $email, string $taxId, int $rowNumber = 2): array
{
    return ['name' => 'Pessoa', 'email' => $email, 'tax_id' => $taxId, '__row_number' => $rowNumber];
}

function createImportedPerson(string $email, string $taxId): User
{
    $user = User::factory()->create(['email' => $email]);
    Detail::factory()->for($user)->create(['tax_id' => $taxId]);

    return $user;
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<ImportRowOutcome>
 */
function classifyImportOutcomes(array $rows, Company $company): array
{
    return resolve(ClassifyImportRowsAction::class)
        ->execute(collect($rows), $company)
        ->map(fn (ClassifiedImportRowDTO $row): ImportRowOutcome => $row->outcome)
        ->all();
}

it('creates a person whose email and tax_id are not registered', function (): void {
    $company = Company::factory()->create();

    expect(classifyImportOutcomes([classifyImportRow('nova@empresa.com', '11111111111')], $company))->toBe([ImportRowOutcome::Create]);
});

it('keeps the first row and skips later rows repeating its email or tax_id', function (): void {
    $company = Company::factory()->create();

    $outcomes = classifyImportOutcomes([
        classifyImportRow('ana@empresa.com', '11111111111'),
        classifyImportRow('ANA@empresa.com ', '22222222222'),
        classifyImportRow('outra@empresa.com', '11111111111'),
    ], $company);

    expect($outcomes)->toBe([ImportRowOutcome::Create, ImportRowOutcome::DuplicateInSheet, ImportRowOutcome::DuplicateInSheet]);
});

it('skips a later row repeating the tax_id of a row that was itself skipped', function (): void {
    $company = Company::factory()->create();

    $outcomes = classifyImportOutcomes([
        classifyImportRow('a@empresa.com', '11111111111'),
        classifyImportRow('a@empresa.com', '22222222222'),
        classifyImportRow('b@empresa.com', '22222222222'),
    ], $company);

    expect($outcomes)->toBe([ImportRowOutcome::Create, ImportRowOutcome::DuplicateInSheet, ImportRowOutcome::DuplicateInSheet]);
});

it('skips a later row that reaches the same registered person through the other key', function (): void {
    $company = Company::factory()->create();
    createImportedPerson('ana@empresa.com', '11111111111');

    $outcomes = classifyImportOutcomes([
        classifyImportRow('outra@empresa.com', '11111111111'),
        classifyImportRow('ana@empresa.com', '22222222222'),
    ], $company);

    expect($outcomes)->toBe([ImportRowOutcome::LinkByTaxId, ImportRowOutcome::DuplicateInSheet]);
});

it('links a person found by the tax_id under another email, reporting that access stays with the account email', function (): void {
    $company = Company::factory()->create();
    $person = createImportedPerson('pessoal@gmail.com', '55555555555');

    $classified = resolve(ClassifyImportRowsAction::class)->execute(collect([classifyImportRow('corporativo@empresa.com', '55555555555')]), $company)->first();

    expect($classified->outcome)->toBe(ImportRowOutcome::LinkByTaxId)
        ->and($classified->userId)->toBe($person->getKey())
        ->and($classified->toReport()->message)->toBe('Já tinha cadastro no ' . config('app.name') . ' com outro e-mail, achado pelo CPF, e foi vinculado à empresa. O acesso continua pelo e-mail do próprio cadastro.');
});

it('sends to support an email whose account has another tax_id, instead of linking or reactivating it', function (bool $inactiveEmployeeHere): void {
    $company = Company::factory()->create();
    $person = createImportedPerson('maria@gmail.com', '11111111111');

    if ($inactiveEmployeeHere) {
        $company->employees()->attach($person->getKey(), ['role' => Roles::Employee->value, 'active' => false]);
    }

    expect(classifyImportOutcomes([classifyImportRow('maria@gmail.com', '22222222222')], $company))->toBe([ImportRowOutcome::TaxIdMismatch]);
})->with([
    'account without a membership here' => false,
    'inactive employee of the company' => true,
]);

it('skips an active member of the company found by email or by tax_id', function (string $email, string $taxId): void {
    $company = Company::factory()->create();
    $member = createImportedPerson('membro@empresa.com', '33333333333');
    $company->employees()->attach($member->getKey(), ['role' => Roles::Employee->value]);

    expect(classifyImportOutcomes([classifyImportRow($email, $taxId)], $company))->toBe([ImportRowOutcome::AlreadyMember]);
})->with([
    'by email with a new tax_id' => ['membro@empresa.com', '99999999999'],
    'by tax_id with a new email' => ['novo@empresa.com', '33333333333'],
]);

it('skips an owner or manager of the company, active or inactive, instead of making them an employee', function (Roles $role, bool $active): void {
    $company = Company::factory()->create();
    $manager = createImportedPerson('gerente@empresa.com', '33333333333');
    $company->employees()->attach($manager->getKey(), ['role' => $role->value, 'active' => $active]);

    expect(classifyImportOutcomes([classifyImportRow('gerente@empresa.com', '33333333333')], $company))->toBe([ImportRowOutcome::AlreadyMember]);
})->with([
    'active manager' => [Roles::CompanyManager, true],
    'inactive manager' => [Roles::CompanyManager, false],
    'inactive owner' => [Roles::CompanyOwner, false],
]);

it('reactivates an inactive employee of the company', function (): void {
    $company = Company::factory()->create();
    $former = createImportedPerson('inativo@empresa.com', '44444444444');
    $company->employees()->attach($former->getKey(), ['role' => Roles::Employee->value, 'active' => false]);

    $classified = resolve(ClassifyImportRowsAction::class)->execute(collect([classifyImportRow('inativo@empresa.com', '44444444444')]), $company)->first();

    expect($classified->outcome)->toBe(ImportRowOutcome::Reactivate)
        ->and($classified->membershipId)->toBe($company->employees()->find($former->getKey())->pivot->id);
});

it('links a person whose only membership is the default bucket', function (): void {
    $company = Company::factory()->create();
    $person = createImportedPerson('pessoa@empresa.com', '55555555555');
    Company::factory()->create(['slug' => Company::DEFAULT_SLUG])->employees()->attach($person->getKey(), ['role' => Roles::Employee->value]);

    $classified = resolve(ClassifyImportRowsAction::class)->execute(collect([classifyImportRow('pessoa@empresa.com', '55555555555')]), $company)->first();

    expect($classified->outcome)->toBe(ImportRowOutcome::Link)
        ->and($classified->userId)->toBe($person->getKey());
});

it('links an owner or manager of another company, who is not employed there', function (Roles $role): void {
    $company = Company::factory()->create();
    $person = createImportedPerson('pessoa@empresa.com', '55555555555');
    Company::factory()->create()->employees()->attach($person->getKey(), ['role' => $role->value]);

    expect(classifyImportOutcomes([classifyImportRow('pessoa@empresa.com', '55555555555')], $company))->toBe([ImportRowOutcome::Link]);
})->with([
    'owner' => [Roles::CompanyOwner],
    'manager' => [Roles::CompanyManager],
]);

it('sends to support an active employee of another company', function (): void {
    $company = Company::factory()->create();
    $person = createImportedPerson('outra@empresa.com', '66666666666');
    Company::factory()->create()->employees()->attach($person->getKey(), ['role' => Roles::Employee->value]);

    expect(classifyImportOutcomes([classifyImportRow('outra@empresa.com', '66666666666')], $company))->toBe([ImportRowOutcome::EmployedElsewhere]);
});

it('sends to support an inactive employee of the company who now works at another company', function (): void {
    $company = Company::factory()->create();
    $person = createImportedPerson('saiu@empresa.com', '66666666666');
    $company->employees()->attach($person->getKey(), ['role' => Roles::Employee->value, 'active' => false]);
    Company::factory()->create()->employees()->attach($person->getKey(), ['role' => Roles::Employee->value]);

    expect(classifyImportOutcomes([classifyImportRow('saiu@empresa.com', '66666666666')], $company))->toBe([ImportRowOutcome::EmployedElsewhere]);
});

it('sends to support a person whose account was deleted', function (string $email, string $taxId): void {
    $company = Company::factory()->create();
    createImportedPerson('excluida@empresa.com', '77777777777')->delete();

    expect(classifyImportOutcomes([classifyImportRow($email, $taxId)], $company))->toBe([ImportRowOutcome::DeletedAccount]);
})->with([
    'found by email' => ['excluida@empresa.com', '12312312312'],
    'found by tax_id' => ['nova@empresa.com', '77777777777'],
]);

it('sends to support a tax_id kept by a deleted detail, which still holds the unique index', function (): void {
    $company = Company::factory()->create();
    createImportedPerson('ativa@empresa.com', '88888888888')->detail->delete();

    expect(classifyImportOutcomes([classifyImportRow('nova@empresa.com', '88888888888')], $company))->toBe([ImportRowOutcome::DeletedAccount]);
});

it('sends to support an active account found by email whose detail was deleted', function (): void {
    $company = Company::factory()->create();
    createImportedPerson('sem-detalhe@empresa.com', '88888888888')->detail->delete();

    expect(classifyImportOutcomes([classifyImportRow('sem-detalhe@empresa.com', '12312312312')], $company))->toBe([ImportRowOutcome::DeletedAccount]);
});

it('sends to support an email of one person with the tax_id of another', function (): void {
    $company = Company::factory()->create();
    createImportedPerson('maria@empresa.com', '11111111111');
    createImportedPerson('joao@empresa.com', '22222222222');

    expect(classifyImportOutcomes([classifyImportRow('maria@empresa.com', '22222222222')], $company))->toBe([ImportRowOutcome::ConflictingData]);
});
