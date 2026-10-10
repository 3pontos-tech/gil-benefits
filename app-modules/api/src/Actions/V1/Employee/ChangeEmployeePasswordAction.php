<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use TresPontosTech\User\Actions\ChangeUserPasswordAction;

final readonly class ChangeEmployeePasswordAction
{
    public function __construct(private ChangeUserPasswordAction $changePassword) {}

    /**
     * Troca a senha e desconecta os outros aparelhos; o que pediu a troca continua conectado.
     */
    public function handle(User $user, string $password): void
    {
        $this->changePassword->handle($user, $password);

        $user->tokens()->whereKeyNot($user->currentAccessToken()->getKey())->delete();
    }
}
