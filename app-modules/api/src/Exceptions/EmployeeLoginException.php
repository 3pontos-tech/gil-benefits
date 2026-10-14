<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Exceptions;

use RuntimeException;
use TresPontosTech\Api\DTOs\AppAccess;

class EmployeeLoginException extends RuntimeException
{
    /**
     * E-mail desconhecido e senha errada dão a mesma mensagem para não revelar quais contas existem.
     */
    public static function invalidCredentials(): self
    {
        return new self(__('api::auth.failed'));
    }

    /**
     * Senha certa, mas sem acesso ao app: o motivo diz o que fazer (plano da empresa inativo, plano
     * próprio a ativar, ou conta que não é do app).
     */
    public static function denied(AppAccess $access): self
    {
        return new self($access->message());
    }
}
