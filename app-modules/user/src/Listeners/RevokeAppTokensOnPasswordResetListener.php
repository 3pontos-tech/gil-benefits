<?php

declare(strict_types=1);

namespace TresPontosTech\User\Listeners;

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use TresPontosTech\User\Actions\RevokeAppTokensAction;

class RevokeAppTokensOnPasswordResetListener
{
    public function __construct(private readonly RevokeAppTokensAction $revokeAppTokens) {}

    /**
     * Quem perdeu o celular faz "esqueci a senha" no site: o token de 90 dias do aparelho perdido
     * tem que parar de valer ali.
     */
    public function handle(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->revokeAppTokens->handle($event->user);
        }
    }
}
