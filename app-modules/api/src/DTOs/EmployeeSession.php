<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

use App\Models\Users\User;

/**
 * Resultado do login no app: quem entrou e o token do aparelho, em texto puro.
 *
 * O texto puro só existe neste momento; no banco fica apenas o hash.
 */
final readonly class EmployeeSession
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
    ) {}
}
