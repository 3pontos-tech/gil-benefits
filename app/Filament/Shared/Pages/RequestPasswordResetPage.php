<?php

declare(strict_types=1);

namespace App\Filament\Shared\Pages;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use TresPontosTech\User\Support\EmailAddress;

class RequestPasswordResetPage extends RequestPasswordReset
{
    /**
     * O e-mail é gravado em minúsculas; o link de recuperação chega mesmo que a pessoa
     * digite com outra capitalização (#291).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            ...parent::getCredentialsFromFormData($data),
            'email' => EmailAddress::normalize($data['email']),
        ];
    }
}
