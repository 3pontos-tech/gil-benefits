<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\ImportUsersFromFileAction;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

function makeCsvFile(array $rows, array $headers = ['name', 'email', 'phone_number', 'document_id', 'tax_id']): string
{
    $path = tempnam(sys_get_temp_dir(), 'import_test_') . '.csv';
    $handle = fopen($path, 'w');
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('imports users with all fields and attaches them to company', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['João Silva', 'joao@empresa.com', '11999999999', '12345678', '123.456.789-00'],
        ['Maria Costa', 'maria@empresa.com', '21988887777', '87654321', '987.654.321-00'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result['imported'])->toBe(2);
    expect($result['errors'])->toBeEmpty();

    assertDatabaseHas(User::class, ['email' => 'joao@empresa.com']);
    assertDatabaseHas(User::class, ['email' => 'maria@empresa.com']);
    assertDatabaseHas(Detail::class, ['tax_id' => '123.456.789-00']);
    assertDatabaseHas(Detail::class, ['tax_id' => '987.654.321-00']);

    $user = User::query()->where('email', 'joao@empresa.com')->first();
    expect($company->employees()->where('user_id', $user->getKey())->exists())->toBeTrue();
    expect($user->hasRole(Roles::Employee))->toBeTrue();
});

it('imports users without phone_number and still creates detail record', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Pedro Souza', 'pedro@empresa.com', '', '99887766', '111.222.333-44'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result['imported'])->toBe(1);
    expect($result['errors'])->toBeEmpty();

    $user = User::query()->where('email', 'pedro@empresa.com')->first();
    assertDatabaseHas(Detail::class, [
        'user_id' => $user->getKey(),
        'document_id' => '99887766',
        'tax_id' => '111.222.333-44',
        'phone_number' => null,
    ]);
});

it('skips rows with missing document_id or tax_id and reports as error', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Sem Document', 'semdoc@empresa.com', '', '', '111.222.333-44'],
        ['Sem Tax', 'semtax@empresa.com', '', '12345678', ''],
        ['Válido', 'valido@empresa.com', '', '12345678', '123.456.789-00'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result['imported'])->toBe(1);
    expect($result['errors'])->toHaveCount(2);
});

it('skips rows with duplicate email and reports as error', function (): void {
    $company = Company::factory()->create();
    User::factory()->create(['email' => 'existing@empresa.com']);

    $csv = makeCsvFile([
        ['Duplicado', 'existing@empresa.com', '', '11111111', '111.111.111-11'],
        ['Novo', 'novo@empresa.com', '', '22222222', '222.222.222-22'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result['imported'])->toBe(1);
    expect($result['errors'])->toHaveCount(1);
    expect($result['errors'][0]['email'])->toBe('existing@empresa.com');
    expect($result['errors'][0]['message'])->toContain('já cadastrado');
});

it('skips rows with missing name or email and reports as error', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['', 'semNome@empresa.com', '', '11111111', '111.111.111-11'],
        ['Sem Email', '', '', '22222222', '222.222.222-22'],
        ['Válido', 'valido@empresa.com', '', '33333333', '333.333.333-33'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result['imported'])->toBe(1);
    expect($result['errors'])->toHaveCount(2);
});

it('skips rows with invalid email format and reports as error', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Inválido', 'nao-é-um-email', '', '12345678', '123.456.789-00'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result['imported'])->toBe(0);
    expect($result['errors'])->toHaveCount(1);
    expect($result['errors'][0]['message'])->toContain('inválido');
});

it('returns zero imported and empty errors for an empty file', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result['imported'])->toBe(0);
    expect($result['errors'])->toBeEmpty();
});
