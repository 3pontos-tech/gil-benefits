<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Company\Models\DepartmentCategory;
use TresPontosTech\PanelCompany\Filament\Resources\Departments\Pages\ListDepartments;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->owner = User::factory()->companyOwner()->create();
    $this->company = Company::factory()->recycle($this->owner)->create();
    $this->company->employees()->attach($this->owner->getKey());

    $this->category = DepartmentCategory::factory()->create();

    filament()->setCurrentPanel(FilamentPanel::Company->value);
    actingAs($this->owner);
    filament()->setTenant($this->company);
});

it('renders the list page', function (): void {
    livewire(ListDepartments::class)
        ->assertOk();
});

it('shows departments that belong to the current tenant', function (): void {
    $departments = Department::factory()->count(3)->create([
        'company_id' => $this->company->getKey(),
        'category_id' => $this->category->getKey(),
    ]);

    livewire(ListDepartments::class)
        ->assertCanSeeTableRecords($departments);
});

it('does not show departments from another tenant', function (): void {
    $otherOwner = User::factory()->companyOwner()->create();
    $otherCompany = Company::factory()->recycle($otherOwner)->create();

    $ownDepartment = Department::factory()->create([
        'company_id' => $this->company->getKey(),
        'category_id' => $this->category->getKey(),
    ]);

    $foreignDepartments = Department::factory()->count(3)->create([
        'company_id' => $otherCompany->getKey(),
        'category_id' => $this->category->getKey(),
    ]);

    livewire(ListDepartments::class)
        ->assertCanSeeTableRecords([$ownDepartment])
        ->assertCanNotSeeTableRecords($foreignDepartments);
});

it('shows no departments when tenant has none', function (): void {
    Department::factory()->count(3)->create([
        'category_id' => $this->category->getKey(),
    ]);

    livewire(ListDepartments::class)
        ->assertCountTableRecords(0);
});
