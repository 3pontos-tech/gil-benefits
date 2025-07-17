<?php

namespace App\Policies\Users;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $model): bool
    {
        return true;
    }

    public function delete(User $user, User $model): bool
    {
        return true;
    }

    public function restore(User $user, User $model): bool
    {
        return true;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return true;
    }
}
