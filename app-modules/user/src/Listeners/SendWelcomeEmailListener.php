<?php

declare(strict_types=1);

namespace TresPontosTech\User\Listeners;

use Illuminate\Support\Facades\Mail;
use TresPontosTech\User\Events\UserRegistered;
use TresPontosTech\User\Mail\WelcomeUserMail;

class SendWelcomeEmailListener
{
    public function handle(UserRegistered $event): void
    {
        if (blank($event->user->email)) {
            return;
        }

        Mail::to($event->user->email)->send(new WelcomeUserMail($event->user, $event->temporaryPassword));
    }
}
