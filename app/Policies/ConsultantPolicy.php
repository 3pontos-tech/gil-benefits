<?php

namespace App\Policies;

use App\Models\Consultant;
use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConsultantPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Consultant $consultant): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Consultant $consultant): bool
    {
        return true;
    }

    public function delete(User $user, Consultant $consultant): bool
    {
        return true;
    }

    public function restore(User $user, Consultant $consultant): bool
    {
        return true;
    }

    public function forceDelete(User $user, Consultant $consultant): bool
    {
        return true;
    }
}
