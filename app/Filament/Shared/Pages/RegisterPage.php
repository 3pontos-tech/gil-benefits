<?php

declare(strict_types=1);

namespace App\Filament\Shared\Pages;

use Filament\Auth\Pages\Register;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use TresPontosTech\User\Support\EmailAddress;

class RegisterPage extends Register
{
    /**
     * O e-mail é gravado em minúsculas (#291): a validação de único compara a forma
     * normalizada, senão `Ana@x.com` passaria e quebraria no índice do banco ao gravar.
     */
    protected function getEmailFormComponent(): Component
    {
        $component = parent::getEmailFormComponent();

        return $component instanceof TextInput
            ? $component->mutateStateForValidationUsing(fn (?string $state): ?string => EmailAddress::normalize($state))
            : $component;
    }
}
