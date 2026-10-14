<?php

namespace App\Observers;

use App\Models\Users\User;
use TresPontosTech\Permissions\Roles;

class UserObserver
{
    public function created(User $user): void
    {
        $user->assignRole(Roles::User->value);
    }
}
