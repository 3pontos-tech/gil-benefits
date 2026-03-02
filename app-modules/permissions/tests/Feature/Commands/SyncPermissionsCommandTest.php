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

it('synchronizes roles and permissions', function (): void {
    // Ensure the default super admin user exists
    User::factory()->create([
        'email' => 'admin@5pontos.com',
    ]);

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
