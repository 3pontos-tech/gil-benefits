<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;

it('allows SuperAdmin to perform all actions', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Roles::SuperAdmin->value);

    $consultant = Consultant::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Consultant::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $consultant))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Consultant::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $consultant))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $consultant))->toBeTrue()
        ->and(Gate::forUser($user)->allows('restore', $consultant))->toBeTrue()
        ->and(Gate::forUser($user)->allows('forceDelete', $consultant))->toBeTrue();
});

it('denies user without permissions on all actions', function (): void {
    $user = User::factory()->create();
    $user->syncRoles([]);

    $consultant = Consultant::factory()->create();

    expect(Gate::forUser($user)->denies('viewAny', Consultant::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', $consultant))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Consultant::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', $consultant))->toBeTrue()
        ->and(Gate::forUser($user)->denies('delete', $consultant))->toBeTrue()
        ->and(Gate::forUser($user)->denies('restore', $consultant))->toBeTrue()
        ->and(Gate::forUser($user)->denies('forceDelete', $consultant))->toBeTrue();
});

it('allows user with viewAny permission to viewAny', function (): void {
    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->givePermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Consultant::class));

    expect(Gate::forUser($user)->allows('viewAny', Consultant::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', Consultant::factory()->create()))->toBeTrue();
});

it('allows user with view permission to view', function (): void {
    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->givePermissionTo(PermissionsEnum::View->buildPermissionFor(Consultant::class));

    expect(Gate::forUser($user)->allows('view', Consultant::factory()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Consultant::class))->toBeTrue();
});

it('allows user with create permission to create', function (): void {
    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->givePermissionTo(PermissionsEnum::Create->buildPermissionFor(Consultant::class));

    expect(Gate::forUser($user)->allows('create', Consultant::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', Consultant::factory()->create()))->toBeTrue();
});
