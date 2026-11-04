<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Exceptions\Employee\Auth;

use RuntimeException;

class PasswordResetTokenException extends RuntimeException
{
    /**
     * Token vencido, já usado ou de outro e-mail: para a pessoa, o tempo acabou e o caminho é pedir
     * outro código. Uma mensagem só também não diz quem tem conta.
     */
    public static function expired(): self
    {
        return new self(__('api::auth.password_reset_expired'));
    }
}
