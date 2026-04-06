<?php

declare(strict_types=1);

namespace App\Policies\Users;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Permissions\PermissionsEnum;

class DetailPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Detail::class));
    }

    public function view(User $user, Detail $detail): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::View->buildPermissionFor(Detail::class));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Create->buildPermissionFor(Detail::class));
    }

    public function update(User $user, Detail $detail): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Update->buildPermissionFor(Detail::class));
    }

    public function delete(User $user, Detail $detail): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Delete->buildPermissionFor(Detail::class));
    }

    public function restore(User $user, Detail $detail): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Restore->buildPermissionFor(Detail::class));
    }

    public function forceDelete(User $user, Detail $detail): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ForceDelete->buildPermissionFor(Detail::class));
    }
}
