<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Exceptions\Employee\Auth;

use RuntimeException;

class PasswordResetCodeException extends RuntimeException
{
    /**
     * E-mail sem conta, código errado, vencido ou esgotado e conta bloqueada dão a mesma mensagem:
     * qualquer diferença diria quem tem conta.
     */
    public static function invalid(): self
    {
        return new self(__('api::auth.password_code_invalid'));
    }
}
