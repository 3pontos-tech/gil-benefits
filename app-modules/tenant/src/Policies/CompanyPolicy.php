<?php

namespace TresPontosTech\Tenant\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\PermissionsEnum;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Company::class));
    }

    public function view(User $user, Company $company): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::View->buildPermissionFor(Company::class));
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Update->buildPermissionFor(Company::class));
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Delete->buildPermissionFor(Company::class));
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Restore->buildPermissionFor(Company::class));
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ForceDelete->buildPermissionFor(Company::class));
    }
}
