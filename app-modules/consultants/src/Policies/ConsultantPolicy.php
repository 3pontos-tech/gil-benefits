<?php

namespace TresPontosTech\Consultants\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Consultants\Models\Consultant;

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
