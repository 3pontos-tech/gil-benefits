<?php

namespace App\Policies\Plans;

use App\Models\Plans\Plan;
use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Plan $plan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Plan $plan): bool
    {
        return true;
    }

    public function delete(User $user, Plan $plan): bool
    {
        return true;
    }

    public function restore(User $user, Plan $plan): bool
    {
        return true;
    }

    public function forceDelete(User $user, Plan $plan): bool
    {
        return true;
    }
}
