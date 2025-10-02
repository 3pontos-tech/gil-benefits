<?php

namespace TresPontosTech\Plans\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Plans\Models\Item;

class ItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Item $item): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Item $item): bool
    {
        return true;
    }

    public function delete(User $user, Item $item): bool
    {
        return true;
    }

    public function restore(User $user, Item $item): bool
    {
        return true;
    }

    public function forceDelete(User $user, Item $item): bool
    {
        return true;
    }
}
