<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions\Tests\Feature\Commands;

use App\Models\Users\User;
use Illuminate\Support\Facades\Artisan;
use TresPontosTech\Permissions\Permission;
use TresPontosTech\Permissions\Role;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    User::factory()->create(['email' => 'admin@5pontos.com']);
});

it('synchronizes roles and permissions', function (): void {
    Artisan::call('sync:permissions');

    $permissionCount = Permission::query()->count();

    foreach (Roles::cases() as $role) {
        assertDatabaseHas('rbac_roles', [
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    assertDatabaseCount('rbac_permissions', $permissionCount);

    $superAdmin = Role::findByName(Roles::SuperAdmin->value);
    expect($superAdmin->permissions)->toHaveCount($permissionCount);

    $user = User::query()->where('email', 'admin@5pontos.com')->first();
    expect($user->hasRole(Roles::SuperAdmin->value))->toBeTrue();
});

it('is idempotent — running twice does not duplicate permissions', function (): void {
    // First run already happened via PermissionSeeder in TestCase
    $firstPermissionNames = Permission::query()->orderBy('name')->pluck('name')->all();
    $firstRoleNames = Role::query()->orderBy('name')->pluck('name')->all();
    $firstSuperAdminPermissionIds = Role::findByName(Roles::SuperAdmin->value)
        ->permissions()
        ->pluck('rbac_permissions.id')
        ->sort()
        ->values()
        ->all();

    Artisan::call('sync:permissions');

    $secondPermissionNames = Permission::query()->orderBy('name')->pluck('name')->all();
    $secondRoleNames = Role::query()->orderBy('name')->pluck('name')->all();
    $secondSuperAdminPermissionIds = Role::findByName(Roles::SuperAdmin->value)
        ->permissions()
        ->pluck('rbac_permissions.id')
        ->sort()
        ->values()
        ->all();

    expect($firstPermissionNames)->toBe($secondPermissionNames)
        ->and($firstRoleNames)->toBe($secondRoleNames)
        ->and($firstSuperAdminPermissionIds)->toBe($secondSuperAdminPermissionIds);
});

it('assigns all permissions to the SuperAdmin role', function (): void {
    // sync:permissions already ran via PermissionSeeder in TestCase
    $allPermissionIds = Permission::query()->pluck('id')->sort()->values()->all();
    $superAdminRole = Role::findByName(Roles::SuperAdmin->value);
    $superAdminPermissionIds = $superAdminRole->permissions()
        ->pluck('rbac_permissions.id')
        ->sort()
        ->values()
        ->all();

    expect($superAdminPermissionIds)->toBe($allPermissionIds);
});
