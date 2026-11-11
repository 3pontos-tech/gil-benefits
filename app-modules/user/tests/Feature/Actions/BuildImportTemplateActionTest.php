<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Spatie\SimpleExcel\SimpleExcelReader;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\User\Actions\BuildImportTemplateAction;
use TresPontosTech\User\Actions\ParseUsersFromFileAction;

function readTemplateSheet(string $path, string $sheet): array
{
    return SimpleExcelReader::create($path)->fromSheetName($sheet)->getRows()->all();
}

it('lists the company departments on a second sheet and uses the first one in the example row', function (): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => 'Vendas']);
    Department::factory()->for($company)->create(['name' => 'Engenharia']);
    Department::factory()->for($company)->create(['name' => 'Jurídico'])->delete();
    Department::factory()->create(['name' => 'Financeiro']);

    $path = resolve(BuildImportTemplateAction::class)->execute($company);

    $employees = readTemplateSheet($path, BuildImportTemplateAction::EMPLOYEES_SHEET);
    $departments = readTemplateSheet($path, BuildImportTemplateAction::DEPARTMENTS_SHEET);

    File::delete($path);

    expect($employees)->toBe([[
        'name' => 'João Silva',
        'email' => 'joaosilva@email.com',
        'phone_number' => '11987654321',
        'document_id' => '12345678',
        'tax_id' => '12345678901',
        'department' => 'Engenharia',
    ]])
        ->and($departments)->toBe([
            ['department' => 'Engenharia'],
            ['department' => 'Vendas'],
        ]);
});

it('leaves the example department blank when the company has no departments', function (): void {
    $company = Company::factory()->create();

    $path = resolve(BuildImportTemplateAction::class)->execute($company);

    $employees = readTemplateSheet($path, BuildImportTemplateAction::EMPLOYEES_SHEET);
    $departments = readTemplateSheet($path, BuildImportTemplateAction::DEPARTMENTS_SHEET);

    File::delete($path);

    expect($employees[0]['department'])->toBe('')
        ->and($departments)->toBe([]);
});

it('is read back by the importer from the employees sheet only', function (): void {
    $company = Company::factory()->create();
    Department::factory()->for($company)->create(['name' => 'Vendas']);

    $path = resolve(BuildImportTemplateAction::class)->execute($company);

    $rows = resolve(ParseUsersFromFileAction::class)->execute($path, 'xlsx');

    File::delete($path);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['email'])->toBe('joaosilva@email.com')
        ->and($rows->first()['department'])->toBe('Vendas');
});
