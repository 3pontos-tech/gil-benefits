<?php

declare(strict_types=1);

namespace TresPontosTech\User\Support;

use Illuminate\Support\Str;

/**
 * Forma canônica de um e-mail no sistema: sem espaços nas pontas e em minúsculas.
 *
 * É a mesma regra na gravação (mutator do User), na validação de único e em toda busca
 * por e-mail, para `Maria@X.com` e `maria@x.com` serem sempre a mesma conta (#291).
 */
final class EmailAddress
{
    /**
     * @return ($email is string ? string : null)
     */
    public static function normalize(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        return Str::of($email)->trim()->lower()->toString();
    }
}
