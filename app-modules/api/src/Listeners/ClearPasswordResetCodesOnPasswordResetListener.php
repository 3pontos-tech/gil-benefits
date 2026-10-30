<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Listeners;

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use TresPontosTech\Api\Models\PasswordResetCode;

class ClearPasswordResetCodesOnPasswordResetListener
{
    /**
     * Um reset por qualquer caminho, inclusive pelo link do site, apaga o código pendente do app e
     * zera os erros da conta, liberando de novo a recuperação por código.
     */
    public function handle(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            PasswordResetCode::query()->where('user_id', $event->user->getKey())->delete();
        }
    }
}
