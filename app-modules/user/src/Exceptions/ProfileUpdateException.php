<?php

declare(strict_types=1);

namespace TresPontosTech\User\Exceptions;

use RuntimeException;

class ProfileUpdateException extends RuntimeException
{
    /**
     * `user_details` exige CPF; sem ele não há onde guardar o telefone.
     */
    public static function missingDetail(): self
    {
        return new self(__('user::profile.errors.missing_detail'));
    }
}
