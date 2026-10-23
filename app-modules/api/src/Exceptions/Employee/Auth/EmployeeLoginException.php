<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Exceptions\Employee\Auth;

use RuntimeException;
use TresPontosTech\Api\DTOs\Employee\Auth\AppAccess;

class EmployeeLoginException extends RuntimeException
{
    /**
     * E-mail desconhecido e senha errada dão a mesma mensagem para não revelar quais contas existem.
     */
    public static function invalidCredentials(): self
    {
        return new self(__('api::auth.failed'));
    }

    public static function denied(AppAccess $access): self
    {
        return new self($access->message());
    }
}
