<?php

namespace TresPontosTech\Consultants\Observers;

use App\Models\Users\User;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantObserver
{
    public function created(Consultant $consultant): void
    {
        $user = User::query()->create([
            'name' => $consultant->name,
            'email' => $consultant->email,
            'password' => $consultant->email,
        ]);

        $consultant->user()->associate($user)->save();
    }
}
