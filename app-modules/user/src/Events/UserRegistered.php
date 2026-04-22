<?php

declare(strict_types=1);

namespace TresPontosTech\User\Events;

use App\Models\Users\User;
use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Permissions\Roles;

final readonly class UserRegistered
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public Roles $role,
        public ?string $temporaryPassword = null,
    ) {}
}
