<?php

declare(strict_types=1);

namespace App\Policies\Users;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Permissions\PermissionsEnum;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(User::class));
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::View->buildPermissionFor(User::class));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Create->buildPermissionFor(User::class));
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Update->buildPermissionFor(User::class));
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Delete->buildPermissionFor(User::class));
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Restore->buildPermissionFor(User::class));
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ForceDelete->buildPermissionFor(User::class));
    }
}
