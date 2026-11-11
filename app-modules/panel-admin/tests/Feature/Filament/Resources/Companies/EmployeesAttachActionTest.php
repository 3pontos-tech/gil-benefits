<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\File;
use Spatie\SimpleExcel\SimpleExcelReader;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\EmployeesRelationManager;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\BuildImportTemplateAction;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
    $this->company = Company::factory()->create();
});

it('attaches an existing user as an employee', function (): void {
    $person = User::factory()->create();

    livewire(EmployeesRelationManager::class, ['ownerRecord' => $this->company, 'pageClass' => EditCompany::class])
        ->callAction(TestAction::make('attach')->table(), data: ['recordId' => $person->getKey()])
        ->assertHasNoActionErrors();

    expect($this->company->employees()->whereKey($person->getKey())->first()?->pivot->role)->toBe(Roles::Employee);
});

it('refuses to attach someone already employed elsewhere, with a message instead of an error', function (): void {
    $elsewhere = Company::factory()->create();
    $person = User::factory()->create();
    $elsewhere->employees()->attach($person->getKey());

    livewire(EmployeesRelationManager::class, ['ownerRecord' => $this->company, 'pageClass' => EditCompany::class])
        ->callAction(TestAction::make('attach')->table(), data: ['recordId' => $person->getKey()])
        ->assertNotified();

    expect($this->company->employees()->whereKey($person->getKey())->exists())->toBeFalse();
});

it('downloads the import template with only this company departments from the import modal', function (): void {
    Department::factory()->for($this->company)->create(['name' => 'Vendas']);
    Department::factory()->create(['name' => 'Outra Empresa']);

    $component = livewire(EmployeesRelationManager::class, ['ownerRecord' => $this->company, 'pageClass' => EditCompany::class])
        ->callAction([
            TestAction::make('import-users')->table(),
            TestAction::make('download_template')->schemaComponent('file'),
        ])
        ->assertFileDownloaded(BuildImportTemplateAction::FILENAME);

    $path = tempnam(sys_get_temp_dir(), 'downloaded_') . '.xlsx';
    file_put_contents($path, base64_decode((string) data_get($component->effects, 'download.content')));
    $departments = SimpleExcelReader::create($path)->fromSheetName(BuildImportTemplateAction::DEPARTMENTS_SHEET)->getRows()->all();
    File::delete($path);

    expect($departments)->toBe([['department' => 'Vendas']]);
});
