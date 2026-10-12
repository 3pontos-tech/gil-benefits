<?php

namespace App\Observers;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;
use TresPontosTech\Permissions\Roles;

class UserObserver
{
    public function created(User $user): void
    {
        $user->assignRole(Roles::User->value);
    }

    /**
     * Senha trocada, por qualquer caminho (esqueci a senha, perfil do painel, admin ou app),
     * desconecta os aparelhos do app: quem perdeu o celular troca a senha e o token de 90 dias
     * do aparelho perdido deixa de valer. Quando a troca vem do app, o token da própria requisição
     * (o Bearer) é poupado e o aparelho que pediu continua conectado.
     */
    public function updated(User $user): void
    {
        if (! $user->wasChanged('password')) {
            return;
        }

        $bearer = request()->bearerToken();
        $currentToken = $bearer === null ? null : PersonalAccessToken::findToken($bearer);

        $user->tokens()
            ->when($currentToken instanceof PersonalAccessToken, fn (Builder $tokens): Builder => $tokens->whereKeyNot($currentToken->getKey()))
            ->delete();
    }
}
