<?php

declare(strict_types=1);

namespace TresPontosTech\User\Listeners;

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;

class EndWebSessionsOnPasswordResetListener
{
    /**
     * Depois de um reset, ninguém continua logado no painel com a senha antiga, como recomendam o
     * OWASP e o Copenhagen Book. Só vale com sessões no banco, o driver do projeto: nos outros, não
     * há como achar as sessões de uma pessoa.
     */
    public function handle(PasswordReset $event): void
    {
        if (! $event->user instanceof User || config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table((string) config('session.table', 'sessions'))
            ->where('user_id', $event->user->getKey())
            ->delete();
    }
}
