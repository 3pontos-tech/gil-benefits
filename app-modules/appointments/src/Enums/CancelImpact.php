<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Enums;

/**
 * O que acontece com a cota ou o crédito avulso se o colaborador cancelar agora.
 * "Crédito" é genérico de propósito, como nos avisos do painel: cobre cota mensal e crédito avulso.
 */
enum CancelImpact: string
{
    case ReturnsCredit = 'returns_credit';
    case LosesCredit = 'loses_credit';
}
