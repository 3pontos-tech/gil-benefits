<?php

namespace TresPontosTech\Admin\Policies;

use App\Models\Users\User;

class BetterMailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
