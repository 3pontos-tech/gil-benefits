<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\ClassifyImportRowsAction;
use TresPontosTech\User\DTOs\ClassifiedImportRowDTO;
use TresPontosTech\User\Enums\ImportRowOutcome;

function classifyRow(string $email, string $taxId, int $rowNumber = 2): array
{
    return ['name' => 'Pessoa', 'email' => $email, 'tax_id' => $taxId, '__row_number' => $rowNumber];
}

function existingPerson(string $email, string $taxId): User
{
    $user = User::factory()->create(['email' => $email]);
    Detail::factory()->for($user)->create(['tax_id' => $taxId]);

    return $user;
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<ImportRowOutcome>
 */
function outcomesFor(array $rows, Company $company): array
{
    return resolve(ClassifyImportRowsAction::class)
        ->execute(collect($rows), $company)
        ->map(fn (ClassifiedImportRowDTO $row): ImportRowOutcome => $row->outcome)
        ->all();
}

it('creates a person whose email and tax_id are not registered', function (): void {
    $company = Company::factory()->create();

    expect(outcomesFor([classifyRow('nova@empresa.com', '11111111111')], $company))->toBe([ImportRowOutcome::Create]);
});

it('keeps the first row and skips later rows repeating its email or tax_id', function (): void {
    $company = Company::factory()->create();

    $outcomes = outcomesFor([
        classifyRow('ana@empresa.com', '11111111111'),
        classifyRow('ANA@empresa.com ', '22222222222'),
        classifyRow('outra@empresa.com', '11111111111'),
    ], $company);

    expect($outcomes)->toBe([ImportRowOutcome::Create, ImportRowOutcome::DuplicateInSheet, ImportRowOutcome::DuplicateInSheet]);
});

it('skips an active member of the company found by email or by tax_id', function (array $row): void {
    $company = Company::factory()->create();
    $member = existingPerson('membro@empresa.com', '33333333333');
    $company->employees()->attach($member->getKey(), ['role' => Roles::Employee->value]);

    expect(outcomesFor([$row], $company))->toBe([ImportRowOutcome::AlreadyMember]);
})->with([
    'by email with a new tax_id' => [['name' => 'Membro', 'email' => 'membro@empresa.com', 'tax_id' => '99999999999', '__row_number' => 2]],
    'by tax_id with a new email' => [['name' => 'Membro', 'email' => 'novo@empresa.com', 'tax_id' => '33333333333', '__row_number' => 2]],
]);

it('skips a manager of the company instead of turning them into an employee', function (): void {
    $company = Company::factory()->create();
    $manager = existingPerson('gerente@empresa.com', '33333333333');
    $company->employees()->attach($manager->getKey(), ['role' => Roles::CompanyManager->value]);

    expect(outcomesFor([classifyRow('gerente@empresa.com', '33333333333')], $company))->toBe([ImportRowOutcome::AlreadyMember]);
});

it('reactivates an inactive employee of the company', function (): void {
    $company = Company::factory()->create();
    $former = existingPerson('inativo@empresa.com', '44444444444');
    $company->employees()->attach($former->getKey(), ['role' => Roles::Employee->value, 'active' => false]);

    $classified = resolve(ClassifyImportRowsAction::class)->execute(collect([classifyRow('inativo@empresa.com', '44444444444')]), $company)->first();

    expect($classified->outcome)->toBe(ImportRowOutcome::Reactivate)
        ->and($classified->membershipId)->toBe($company->employees()->find($former->getKey())->pivot->id);
});

it('links a person whose only membership is the default bucket or who owns another company', function (string $persona): void {
    $company = Company::factory()->create();
    $person = existingPerson('pessoa@empresa.com', '55555555555');

    $other = $persona === 'default bucket member'
        ? Company::factory()->create(['slug' => Company::DEFAULT_SLUG])
        : Company::factory()->create();
    $other->employees()->attach($person->getKey(), [
        'role' => $persona === 'default bucket member' ? Roles::Employee->value : Roles::CompanyOwner->value,
    ]);

    $classified = resolve(ClassifyImportRowsAction::class)->execute(collect([classifyRow('pessoa@empresa.com', '55555555555')]), $company)->first();

    expect($classified->outcome)->toBe(ImportRowOutcome::Link)
        ->and($classified->userId)->toBe($person->getKey());
})->with(['default bucket member', 'owner of another company']);

it('sends to support an active employee of another company', function (): void {
    $company = Company::factory()->create();
    $person = existingPerson('outra@empresa.com', '66666666666');
    Company::factory()->create()->employees()->attach($person->getKey(), ['role' => Roles::Employee->value]);

    expect(outcomesFor([classifyRow('outra@empresa.com', '66666666666')], $company))->toBe([ImportRowOutcome::EmployedElsewhere]);
});

it('sends to support an inactive employee of the company who now works at another company', function (): void {
    $company = Company::factory()->create();
    $person = existingPerson('saiu@empresa.com', '66666666666');
    $company->employees()->attach($person->getKey(), ['role' => Roles::Employee->value, 'active' => false]);
    Company::factory()->create()->employees()->attach($person->getKey(), ['role' => Roles::Employee->value]);

    expect(outcomesFor([classifyRow('saiu@empresa.com', '66666666666')], $company))->toBe([ImportRowOutcome::EmployedElsewhere]);
});

it('sends to support a person whose account was deleted', function (string $match): void {
    $company = Company::factory()->create();
    $person = existingPerson('excluida@empresa.com', '77777777777');
    $person->delete();

    $row = $match === 'email'
        ? classifyRow('excluida@empresa.com', '12312312312')
        : classifyRow('nova@empresa.com', '77777777777');

    expect(outcomesFor([$row], $company))->toBe([ImportRowOutcome::DeletedAccount]);
})->with(['email', 'tax_id']);

it('sends to support a tax_id kept by a deleted detail, which still holds the unique index', function (): void {
    $company = Company::factory()->create();
    existingPerson('ativa@empresa.com', '88888888888')->detail->delete();

    expect(outcomesFor([classifyRow('nova@empresa.com', '88888888888')], $company))->toBe([ImportRowOutcome::DeletedAccount]);
});

it('sends to support an email of one person with the tax_id of another', function (): void {
    $company = Company::factory()->create();
    existingPerson('maria@empresa.com', '11111111111');
    existingPerson('joao@empresa.com', '22222222222');

    expect(outcomesFor([classifyRow('maria@empresa.com', '22222222222')], $company))->toBe([ImportRowOutcome::ConflictingData]);
});
