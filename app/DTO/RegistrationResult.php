<?php

namespace App\DTO;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

class RegistrationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?User $user = null,
        public readonly ?string $error = null,
        public readonly ?Company $company = null,
    ) {}

    public static function success(User $user, Company $company): self
    {
        return new self(
            success: true,
            user: $user,
            company: $company,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }
}
