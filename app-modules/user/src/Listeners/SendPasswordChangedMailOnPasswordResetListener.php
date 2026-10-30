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
     */
    public function handle(PasswordReset $event): void
    {
        if (! $event->user instanceof User || blank($event->user->email)) {
            return;
        }

        Mail::to($event->user)->queue(new PasswordChangedMail($event->user, now()));
    }
}
