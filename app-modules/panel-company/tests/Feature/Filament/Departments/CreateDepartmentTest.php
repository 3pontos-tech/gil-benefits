<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Company\Enums\DepartmentCategory;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Filament\Resources\Departments\Pages\CreateDepartment;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->owner = User::factory()->companyOwner()->create();
    $this->company = Company::factory()->recycle($this->owner)->create();
    $this->company->employees()->attach($this->owner->getKey());

    filament()->setCurrentPanel(FilamentPanel::Company->value);
    actingAs($this->owner);
    filament()->setTenant($this->company);
});

it('creates a department and assigns the current tenant automatically', function (): void {
    livewire(CreateDepartment::class)
        ->fillForm([
            'name' => 'Financeiro',
            'category' => DepartmentCategory::Finance->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('departments', [
        'name' => 'Financeiro',
        'company_id' => $this->company->getKey(),
        'category' => DepartmentCategory::Finance->value,
    ]);
});

it('never assigns another tenant company_id on create', function (): void {
    $otherOwner = User::factory()->companyOwner()->create();
    $otherCompany = Company::factory()->recycle($otherOwner)->create();

    livewire(CreateDepartment::class)
        ->fillForm([
            'name' => 'RH',
            'category' => DepartmentCategory::HumanResources->value,
            'company_id' => $otherCompany->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('departments', [
        'name' => 'RH',
        'company_id' => $this->company->getKey(),
    ]);

    $this->assertDatabaseMissing('departments', [
        'name' => 'RH',
        'company_id' => $otherCompany->getKey(),
    ]);
});

describe('uniqueness', function (): void {
    it('prevents the same company from creating two departments with the same name', function (): void {
        Department::factory()->create([
            'company_id' => $this->company->getKey(),
            'name' => 'RH',
        ]);

        livewire(CreateDepartment::class)
            ->fillForm(['name' => 'RH', 'category' => DepartmentCategory::HumanResources->value])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    });

    it('allows two different companies to create a department with the same name', function (): void {
        $otherOwner = User::factory()->companyOwner()->create();
        $otherCompany = Company::factory()->recycle($otherOwner)->create();

        Department::factory()->create([
            'company_id' => $otherCompany->getKey(),
            'name' => 'RH',
        ]);

        livewire(CreateDepartment::class)
            ->fillForm(['name' => 'RH', 'category' => DepartmentCategory::HumanResources->value])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', [
            'name' => 'RH',
            'company_id' => $this->company->getKey(),
        ]);
    });
});

describe('validation', function (): void {
    it('requires name', function (): void {
        livewire(CreateDepartment::class)
            ->fillForm(['name' => '', 'category' => DepartmentCategory::Finance->value])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    });

    it('requires category', function (): void {
        livewire(CreateDepartment::class)
            ->fillForm(['name' => 'TI', 'category' => null])
            ->call('create')
            ->assertHasFormErrors(['category' => 'required']);
    });

    it('enforces max length on name', function (): void {
        livewire(CreateDepartment::class)
            ->fillForm([
                'name' => str_repeat('a', 256),
                'category' => DepartmentCategory::Finance->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'max']);
    });
});
