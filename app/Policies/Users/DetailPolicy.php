<?php

namespace App\Policies\Users;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DetailPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Detail $detail): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Detail $detail): bool
    {
        return true;
    }

    public function delete(User $user, Detail $detail): bool
    {
        return true;
    }

    public function restore(User $user, Detail $detail): bool
    {
        return true;
    }

    public function forceDelete(User $user, Detail $detail): bool
    {
        return true;
    }
}
