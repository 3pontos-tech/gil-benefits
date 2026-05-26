<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use TresPontosTech\Company\Enums\DepartmentCategory;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Filament\Resources\Departments\Pages\EditDepartment;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->owner = User::factory()->companyOwner()->create();
    $this->company = Company::factory()->recycle($this->owner)->create();
    $this->company->employees()->attach($this->owner->getKey());

    $this->department = Department::factory()->create([
        'company_id' => $this->company->getKey(),
        'category' => DepartmentCategory::Finance->value,
        'name' => 'Financeiro',
    ]);

    filament()->setCurrentPanel(FilamentPanel::Company->value);
    actingAs($this->owner);
    filament()->setTenant($this->company);
});

it('can edit a department that belongs to the current tenant', function (): void {
    livewire(EditDepartment::class, ['record' => $this->department->getRouteKey()])
        ->fillForm([
            'name' => 'Financeiro e Contabilidade',
            'category' => DepartmentCategory::Administrative->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('departments', [
        'id' => $this->department->getKey(),
        'name' => 'Financeiro e Contabilidade',
        'category' => DepartmentCategory::Administrative->value,
        'company_id' => $this->company->getKey(),
    ]);
});

it('cannot access a department from another tenant', function (): void {
    $otherOwner = User::factory()->companyOwner()->create();
    $otherCompany = Company::factory()->recycle($otherOwner)->create();

    $foreignDepartment = Department::factory()->create([
        'company_id' => $otherCompany->getKey(),
    ]);

    $this->expectException(ModelNotFoundException::class);

    livewire(EditDepartment::class, ['record' => $foreignDepartment->getRouteKey()]);
});

it('does not change company_id when editing', function (): void {
    $otherOwner = User::factory()->companyOwner()->create();
    $otherCompany = Company::factory()->recycle($otherOwner)->create();

    livewire(EditDepartment::class, ['record' => $this->department->getRouteKey()])
        ->fillForm([
            'name' => 'Alterado',
            'category' => DepartmentCategory::Finance->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('departments', [
        'id' => $this->department->getKey(),
        'company_id' => $this->company->getKey(),
    ]);

    $this->assertDatabaseMissing('departments', [
        'id' => $this->department->getKey(),
        'company_id' => $otherCompany->getKey(),
    ]);
});

describe('uniqueness', function (): void {
    it('prevents renaming to a name already used in the same company', function (): void {
        Department::factory()->create([
            'company_id' => $this->company->getKey(),
            'name' => 'RH',
        ]);

        livewire(EditDepartment::class, ['record' => $this->department->getRouteKey()])
            ->fillForm(['name' => 'RH'])
            ->call('save')
            ->assertHasFormErrors(['name' => 'unique']);
    });

    it('allows saving the same name on the same record', function (): void {
        livewire(EditDepartment::class, ['record' => $this->department->getRouteKey()])
            ->fillForm(['name' => $this->department->name])
            ->call('save')
            ->assertHasNoFormErrors();
    });

    it('allows using a name that exists in another company', function (): void {
        $otherOwner = User::factory()->companyOwner()->create();
        $otherCompany = Company::factory()->recycle($otherOwner)->create();

        Department::factory()->create([
            'company_id' => $otherCompany->getKey(),
            'name' => 'Financeiro',
        ]);

        livewire(EditDepartment::class, ['record' => $this->department->getRouteKey()])
            ->fillForm(['name' => 'Financeiro'])
            ->call('save')
            ->assertHasNoFormErrors();
    });
});

it('can delete a department that belongs to the current tenant', function (): void {
    livewire(EditDepartment::class, ['record' => $this->department->getRouteKey()])
        ->callAction('delete');

    $this->assertSoftDeleted('departments', [
        'id' => $this->department->getKey(),
    ]);
});
