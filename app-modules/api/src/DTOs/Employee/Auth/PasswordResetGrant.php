<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Employee\Auth;

use Carbon\CarbonInterface;

/**
 * O token puro só existe nesta resposta; no banco fica apenas o hash.
 */
final readonly class PasswordResetGrant
{
    public function __construct(
        public string $plainTextToken,
        public CarbonInterface $expiresAt,
    ) {}
}
