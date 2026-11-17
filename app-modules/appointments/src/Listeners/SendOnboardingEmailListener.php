<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Listeners;

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Mail\EmployeeOnboardingMail;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserImported;
use TresPontosTech\User\Events\UserRegistered;

/**
 * Manda o onboarding a quem passa pela consultoria, por qualquer porta de entrada: auto cadastro,
 * criação pela empresa, criação pelo admin (UserRegistered) e importação em massa (UserImported).
 * Consultores e demais papéis também disparam UserRegistered e ficam de fora.
 */
final class SendOnboardingEmailListener
{
    /**
     * @var list<Roles>
     */
    private const array ROLES = [Roles::Employee, Roles::User];

    public function handleUserRegistered(UserRegistered $event): void
    {
        if (! in_array($event->role, self::ROLES, true)) {
            return;
        }

        $this->send($event->user, filled($event->temporaryPassword));
    }

    public function handleUserImported(UserImported $event): void
    {
        $this->send($event->user, hasTemporaryPassword: true);
    }

    private function send(User $user, bool $hasTemporaryPassword): void
    {
        if (blank($user->email)) {
            return;
        }

        Mail::to($user->email)->queue(new EmployeeOnboardingMail($user, $hasTemporaryPassword));
    }
}
