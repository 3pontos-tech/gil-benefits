<?php

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\EmployeesRelationManager;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\Pages\ListUsers;
use TresPontosTech\Permissions\Roles;

use function Pest\Livewire\livewire;

it('is visible to SuperAdmin users', function (): void {
    actingAsSuperAdmin();

    $target = User::factory()->create();

    livewire(ListUsers::class)
        ->assertActionVisible(TestAction::make('assign-role-action')->table($target));
});

it('is hidden from Admin users', function (): void {
    actingAsAdmin();

    $target = User::factory()->create();

    livewire(ListUsers::class)
        ->assertActionHidden(TestAction::make('assign-role-action')->table($target));
});

it('assigns the chosen global role to the target user (global context)', function (): void {
    actingAsSuperAdmin();

    $target = User::factory()->create();

    livewire(ListUsers::class)
        ->callAction(
            TestAction::make('assign-role-action')->table($target),
            data: ['role' => Roles::Consultant->value],
        )
        ->assertHasNoActionErrors();

    expect($target->fresh()->hasRole(Roles::Consultant))->toBeTrue();
});

it('sends a notification when the user already has the global role', function (): void {
    actingAsSuperAdmin();

    $target = User::factory()->create();
    $target->assignRole(Roles::Consultant);

    $roleCountBefore = $target->roles()->count();

    livewire(ListUsers::class)
        ->callAction(
            TestAction::make('assign-role-action')->table($target),
            data: ['role' => Roles::Consultant->value],
        )
        ->assertNotified();

    expect($target->fresh()->roles()->count())->toBe($roleCountBefore);
});

it('assigns a per-tenant role in the pivot when scoped to a company', function (): void {
    actingAsSuperAdmin();

    $owner = User::factory()->companyOwner()->create();
    $company = Company::factory()->recycle($owner)->create();

    $employee = User::factory()->create();
    $company->employees()->attach($employee->getKey(), ['role' => Roles::Employee->value]);

    livewire(EmployeesRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => EditCompany::class,
    ])
        ->callAction(
            TestAction::make('assign-role-action')->table($employee),
            data: ['role' => Roles::CompanyManager->value],
        )
        ->assertHasNoActionErrors();

    $pivotRole = $company->employees()->whereKey($employee->getKey())->first()->pivot->role;

    // The role is written to the pivot for this company, not as a global role.
    expect($pivotRole)->toBe(Roles::CompanyManager)
        ->and($employee->fresh()->hasRole(Roles::CompanyManager))->toBeFalse();
});
