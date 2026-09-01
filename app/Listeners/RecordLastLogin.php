<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Users\User;
use Illuminate\Auth\Events\Login;

/**
 * Carimba o último acesso do usuário a cada login (FLM-41, decisão D-09).
 *
 * Grava sem tocar `updated_at`: o carimbo é telemetria de acesso e não deve
 * mascarar quando o cadastro em si mudou pela última vez.
 */
class RecordLastLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        User::withoutTimestamps(
            fn () => $user->forceFill(['last_login_at' => now()])->saveQuietly()
        );
    }
}
