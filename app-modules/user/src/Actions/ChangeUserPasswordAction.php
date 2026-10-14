<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use App\Models\Users\User;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class ChangeUserPasswordAction
{
    public function __construct(private RevokeAppTokensAction $revokeAppTokens) {}

    /**
     * Trocar a senha desconecta os aparelhos do app; `$keepToken` é o do aparelho que pediu a troca,
     * que continua conectado.
     */
    public function handle(User $user, string $password, ?PersonalAccessToken $keepToken = null): User
    {
        $user->update(['password' => $password]);

        $this->revokeAppTokens->handle($user, $keepToken);

        return $user;
    }
}
