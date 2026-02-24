<?php

namespace TresPontosTech\Consultants\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\PermissionsEnum;

class ConsultantPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Consultant::class));
    }

    public function view(User $user, Consultant $consultant): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Consultant::class));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Create->buildPermissionFor(Consultant::class));
    }

    public function update(User $user, Consultant $consultant): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Update->buildPermissionFor(Consultant::class));
    }

    public function delete(User $user, Consultant $consultant): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Delete->buildPermissionFor(Consultant::class));
    }

    public function restore(User $user, Consultant $consultant): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Restore->buildPermissionFor(Consultant::class));
    }

    public function forceDelete(User $user, Consultant $consultant): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ForceDelete->buildPermissionFor(Consultant::class));
    }
}
