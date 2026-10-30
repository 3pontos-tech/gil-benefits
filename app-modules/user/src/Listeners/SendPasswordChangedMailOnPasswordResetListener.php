<?php

declare(strict_types=1);

namespace TresPontosTech\User\Listeners;

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\User\Mail\PasswordChangedMail;

class SendPasswordChangedMailOnPasswordResetListener
{
    /**
     * O aviso de senha alterada (ASVS 6.3.7, NIST) sai em todo reset, pelo app ou pelo site, para a
     * dona da conta perceber uma troca que não foi ela.
     *
     * Quem nunca entrou está definindo a primeira senha, como o consultor novo pelo link de
     * boas-vindas (#321), e não trocando: o aviso diria que alguém mexeu na conta. O `last_login_at`
     * não tem histórico de antes da coluna existir, então quem não entra desde então também fica
     * sem o aviso; ele iria para a mesma caixa que recebeu o link ou o código.
     */
    public function handle(PasswordReset $event): void
    {
        if (! $event->user instanceof User || blank($event->user->email) || $event->user->last_login_at === null) {
            return;
        }

        Mail::to($event->user)->queue(new PasswordChangedMail($event->user, now()));
    }
}
