<?php

namespace App\Policies\Companies;

use App\Models\Companies\Company;
use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Company $company): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company): bool
    {
        return true;
    }

    public function delete(User $user, Company $company): bool
    {
        return true;
    }

    public function restore(User $user, Company $company): bool
    {
        return true;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return true;
    }
}
