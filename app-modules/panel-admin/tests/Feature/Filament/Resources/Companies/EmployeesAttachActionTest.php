<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Spatie\SimpleExcel\SimpleExcelReader;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\EmployeesRelationManager;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\BuildImportTemplateAction;
use TresPontosTech\User\Jobs\ImportUsersJob;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
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

it('shows a success notification once the import job reports completion', function (): void {
    Cache::put(ImportUsersJob::completionCacheKey((string) auth()->id()), '1700');

    livewire(EmployeesRelationManager::class, ['ownerRecord' => $this->company, 'pageClass' => EditCompany::class])
        ->call('checkImportCompletion')
        ->assertNotified(
            Notification::make()
                ->success()
                ->title('Importação concluída')
                ->body('Todos os 1700 usuários foram importados com sucesso.'),
        );

    expect(Cache::has(ImportUsersJob::completionCacheKey((string) auth()->id())))->toBeFalse();
});

it('keeps people who are not admins away from the company import modal', function (): void {
    $companyOwner = User::factory()->companyOwner()->create();
    $this->company->employees()->attach($companyOwner->getKey(), ['role' => Roles::CompanyOwner->value]);
    Department::factory()->for($this->company)->create(['name' => 'Vendas']);

    auth()->logout();
    actingAs($companyOwner);

    get(route('filament.admin.resources.companies.edit', ['record' => $this->company]))
        ->assertRedirect()
        ->assertDontSee('Vendas');
});
