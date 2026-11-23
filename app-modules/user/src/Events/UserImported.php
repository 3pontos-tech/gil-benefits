<?php

declare(strict_types=1);

namespace TresPontosTech\User\Events;

use App\Models\Users\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Um colaborador criado pela importação em massa. A importação não dispara UserRegistered (ela
 * já manda as boas-vindas e vincula a empresa por conta própria); este evento existe para quem
 * precisa reagir a cada colaborador novo, como o e-mail de onboarding.
 */
final readonly class UserImported
{
    use Dispatchable;

    public function __construct(
        public User $user,
    ) {}
}
