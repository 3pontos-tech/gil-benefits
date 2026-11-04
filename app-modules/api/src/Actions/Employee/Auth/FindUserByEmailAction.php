<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Auth;

use App\Models\Users\User;
use TresPontosTech\User\Support\EmailAddress;

final readonly class FindUserByEmailAction
{
    /**
     * O e-mail é gravado em minúsculas (#291), então a busca normaliza a entrada e compara
     * direto, usando o índice único da coluna. Login e recuperação de senha procuram igual.
     */
    public function handle(string $email): ?User
    {
        return User::query()->where('email', EmailAddress::normalize($email))->first();
    }
}
