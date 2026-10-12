<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Exceptions;

use RuntimeException;
use TresPontosTech\Api\DTOs\EmployeeCompanyAccess;

class EmployeeLoginException extends RuntimeException
{
    /**
     * E-mail desconhecido e senha errada dão a mesma mensagem para não revelar quais contas existem.
     */
    public static function invalidCredentials(): self
    {
        return new self(__('api::auth.failed'));
    }

    public static function noAccess(): self
    {
        return new self(__('api::auth.no_access'));
    }

    /**
     * Senha certa, mas sem o benefício pago em nenhuma empresa: o motivo diz o que fazer.
     */
    public static function withoutBenefit(EmployeeCompanyAccess $access): self
    {
        return new self($access->denialMessage());
    }
}
