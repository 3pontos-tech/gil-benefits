<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\Pages\EditCompany;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\RelationManagers\EmployeesRelationManager;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    $this->company = Company::factory()->createOne();
});

it('should render', function (): void {
    livewire(EditCompany::class, ['record' => $this->company->slug])
        ->assertOk();
});

it('can edit a company', function (): void {
    livewire(EditCompany::class, ['record' => $this->company->slug])
        ->assertOk()
        ->fillForm([
            'name' => 'updated company name',
            'tax_id' => '94.190.305/0001-57',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'id' => $this->company->getKey(),
        'name' => 'updated company name',
        'tax_id' => '94.190.305/0001-57',
    ]);
});

it('should list company members ont edit page', function (): void {
    $employees = User::factory()->count(10)->create();
    $employees->each(fn ($employee) => $this->company->employees()->attach($employee));

    livewire(EditCompany::class, ['record' => $this->company->slug])
        ->assertOk()
        ->assertSeeLivewire(EmployeesRelationManager::class)
        ->assertSee($employees->pluck('name')->toArray());
});

test('employees relation manager', function (): void {
    $employees = User::factory()->count(10)->create();
    $employees->each(fn ($employee) => $this->company->employees()->attach($employee));

    livewire(EmployeesRelationManager::class, [
        'ownerRecord' => $this->company,
        'pageClass' => EditCompany::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($employees);
});
