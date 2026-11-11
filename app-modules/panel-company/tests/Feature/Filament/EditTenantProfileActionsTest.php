<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\File;
use Spatie\SimpleExcel\SimpleExcelReader;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\BuildImportTemplateAction;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->owner = User::factory()->companyOwner()->create();
    $this->company = Company::factory()->recycle($this->owner)->create();
    $this->company->employees()->attach($this->owner->getKey());

    CompanyPlan::factory()->create([
        'company_id' => $this->company->getKey(),
        'status' => CompanyPlanStatusEnum::Active->value,
        'monthly_appointments_per_employee' => 1,
        'starts_at' => now()->subDay(),
        'seats' => 10,
    ]);

    $this->manager = User::factory()->companyManager()->create();
    $this->company->employees()->attach($this->manager->getKey(), ['role' => Roles::CompanyManager->value]);

    $this->employee = User::factory()->employee()->create();
    $this->company->employees()->attach($this->employee->getKey(), ['role' => Roles::Employee->value]);
});

describe('toggle-active action', function (): void {

    it('company manager cannot see deactivate action for the company owner', function (): void {
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->manager);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->assertTableActionHidden('toggle-active', $this->owner);
    });

    it('company owner cannot see deactivate action for themselves', function (): void {
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->assertTableActionHidden('toggle-active', $this->owner);
    });

    it('company manager can see deactivate action for a regular employee', function (): void {
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->manager);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->assertTableActionVisible('toggle-active', $this->employee);
    });

});

describe('toggle-manager action', function (): void {

    it('company owner can see make manager action for a regular employee', function (): void {
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->assertTableActionVisible('toggle-manager', $this->employee);
    });

    it('company manager can see make manager action for a regular employee', function (): void {
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->manager);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->assertTableActionVisible('toggle-manager', $this->employee);
    });

    it('company manager cannot see remove manager action for another manager', function (): void {
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->manager);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->assertTableActionHidden('toggle-manager', $this->manager);
    });

    it('company owner cannot see toggle manager action for themselves', function (): void {
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->assertTableActionHidden('toggle-manager', $this->owner);
    });

    it('company owner can see remove manager action for an existing manager', function (): void {
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->assertTableActionVisible('toggle-manager', $this->manager);
    });

});

describe('single active employer', function (): void {

    it('refuses to reactivate an employee who is now active at another company', function (): void {
        $this->company->employees()->updateExistingPivot($this->employee->getKey(), ['active' => false]);
        $current = Company::factory()->create();
        $current->employees()->attach($this->employee->getKey(), ['role' => Roles::Employee->value]);

        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->callTableAction('toggle-active', $this->employee)
            ->assertNotified(__('panel-company::resources.pages.edit_tenant.employed_elsewhere_notification'));

        expect($this->company->employees()->whereKey($this->employee->getKey())->first()->pivot->active)->toBeFalse();
    });

    it('refuses to demote a manager who is an employee at another company', function (): void {
        $elsewhere = Company::factory()->create();
        $elsewhere->employees()->attach($this->manager->getKey(), ['role' => Roles::Employee->value]);

        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
        filament()->setTenant($this->company);

        livewire(EditTenantProfile::class)
            ->callTableAction('toggle-manager', $this->manager)
            ->assertNotified(__('panel-company::resources.pages.edit_tenant.employed_elsewhere_notification'));

        expect($this->company->employees()->whereKey($this->manager->getKey())->first()->pivot->role)->toBe(Roles::CompanyManager);
    });

});

describe('import-users action', function (): void {

    it('company owner downloads the import template with only this company departments', function (): void {
        Department::factory()->for($this->company)->create(['name' => 'Vendas']);
        Department::factory()->create(['name' => 'Outra Empresa']);

        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
        filament()->setTenant($this->company);

        $component = livewire(EditTenantProfile::class)
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

});
