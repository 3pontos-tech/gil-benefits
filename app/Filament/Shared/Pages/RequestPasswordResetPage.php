<?php

declare(strict_types=1);

namespace App\Filament\Shared\Pages;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
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

    /**
     * E-mail sem conta e pedido repetido em menos de 60 s, que só acontece com conta real, terminam
     * como o envio: a mesma notificação e o formulário limpo, para a página não dizer quem tem conta
     * (#323). O tempo já é igual, porque o broker envia dentro de um Timebox. O formulário é limpo
     * aqui porque o `request()` do Filament só o limpa no sucesso.
     */
    protected function getFailureNotification(string $status): ?Notification
    {
        if (! in_array($status, [Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return parent::getFailureNotification($status);
        }

        $this->form->fill();

        return $this->getSentNotification(Password::RESET_LINK_SENT);
    }
}
