<?php

namespace TresPontosTech\Consultants\Observers;

use App\Models\Users\User;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

class ConsultantObserver
{
    public function created(Consultant $consultant): void
    {
        $user = User::query()->firstOrCreate([
            'email' => $consultant->email,
        ], [
            'name' => $consultant->name,
            'password' => $consultant->email,
        ]);

        $consultant->user()->associate($user)->save();
        event(new UserRegistered($user, Roles::Consultant));
    }
}
