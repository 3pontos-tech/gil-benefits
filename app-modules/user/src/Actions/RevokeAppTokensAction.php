<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use App\Models\Users\User;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevokeAppTokensAction
{
    /**
     * Chamada por quem troca a senha de verdade (app, "esqueci a senha", perfil, admin), e não por
     * um observer: o login do painel regrava o hash quando o custo do bcrypt muda, e isso não pode
     * desconectar o app. O token a manter só vale se for da própria pessoa.
     */
    public function handle(User $user, ?PersonalAccessToken $keep = null): void
    {
        $user->tokens()
            ->when(
                $keep instanceof PersonalAccessToken && $keep->tokenable_id === $user->getKey(),
                fn ($tokens) => $tokens->whereKeyNot($keep?->getKey()),
            )
            ->delete();
    }
}
