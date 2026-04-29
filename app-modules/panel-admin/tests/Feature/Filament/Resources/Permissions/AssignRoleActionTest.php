<?php

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\ListUsers;
use TresPontosTech\Permissions\Roles;

use function Pest\Livewire\livewire;

it('is visible to SuperAdmin users', function (): void {
    actingAsSuperAdmin();

    $target = User::factory()->create();

    livewire(ListUsers::class)
        ->assertActionVisible(TestAction::make('assign-role-action')->table($target));
});

it('is hidden from non-SuperAdmin users', function (): void {
    actingAsAdmin();

    $target = User::factory()->create();

    livewire(ListUsers::class)
        ->assertActionHidden(TestAction::make('assign-role-action')->table($target));
});

it('assigns the chosen role to the target user', function (): void {
    actingAsSuperAdmin();

    $target = User::factory()->create();

    livewire(ListUsers::class)
        ->callAction(
            TestAction::make('assign-role-action')->table($target),
            data: ['role' => Roles::CompanyOwner],
        )
        ->assertHasNoActionErrors();

    expect($target->fresh()->hasRole(Roles::CompanyOwner))->toBeTrue();
});

it('sends a notification when the action is called for a user who already has the role', function (): void {
    actingAsSuperAdmin();

    $target = User::factory()->create();
    $target->assignRole(Roles::Employee);

    livewire(ListUsers::class)
        ->callAction(
            TestAction::make('assign-role-action')->table($target),
            data: ['role' => Roles::Employee],
        )
        ->assertNotified();

    // The user still has the Employee role after the action
    expect($target->fresh()->hasRole(Roles::Employee))->toBeTrue();
});
