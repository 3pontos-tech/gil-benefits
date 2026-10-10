<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use App\Models\Users\User;

final readonly class ChangeUserPasswordAction
{
    /**
     * Grava a nova senha; o cast `hashed` do model cuida do hash.
     */
    public function handle(User $user, string $password): User
    {
        $user->update(['password' => $password]);

        return $user;
    }
}
