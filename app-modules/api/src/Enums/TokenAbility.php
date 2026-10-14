<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Enums;

enum TokenAbility: string
{
    /**
     * Gravado em `personal_access_tokens.abilities` de cada token emitido no login do app:
     * renomear o valor desconecta todo mundo que já está logado.
     */
    case Employee = 'employee';
}
