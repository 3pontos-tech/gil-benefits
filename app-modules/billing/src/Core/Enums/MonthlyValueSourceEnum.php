<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Enums;

/**
 * De onde o valor mensal de um pagante foi tirado (FLM-41, D-01).
 */
enum MonthlyValueSourceEnum: string
{
    /** Assinatura por assento: faixa do SeatPricingTierEnum x quantidade. */
    case SubscriptionSeatTier = 'subscription_seat_tier';

    /** Assinatura avulsa: preço cadastrado do plano. */
    case SubscriptionPrice = 'subscription_price';

    /** Pagante sem nenhuma forma de precificação disponível. */
    case Unknown = 'unknown';
}
