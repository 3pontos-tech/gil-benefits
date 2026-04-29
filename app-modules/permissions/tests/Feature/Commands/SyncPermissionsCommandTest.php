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
    Artisan::call('sync:permissions');
    $firstRunPermissionCount = Permission::query()->count();
    $firstRunRoleCount = Role::query()->count();

    Artisan::call('sync:permissions');
    $secondRunPermissionCount = Permission::query()->count();
    $secondRunRoleCount = Role::query()->count();

    expect($firstRunPermissionCount)->toBe($secondRunPermissionCount);
    expect($firstRunRoleCount)->toBe($secondRunRoleCount);
});

it('assigns all permissions to the SuperAdmin role', function (): void {
    Artisan::call('sync:permissions');

    $allPermissions = Permission::query()->count();
    $superAdminRole = Role::findByName(Roles::SuperAdmin->value);

    expect($superAdminRole->permissions()->count())->toBe($allPermissions);
});
