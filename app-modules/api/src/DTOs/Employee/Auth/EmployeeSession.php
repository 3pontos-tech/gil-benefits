<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Employee\Auth;

use App\Models\Users\User;

/**
 * O texto puro só existe neste momento; no banco fica apenas o hash.
 */
final readonly class EmployeeSession
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
    ) {}
}
