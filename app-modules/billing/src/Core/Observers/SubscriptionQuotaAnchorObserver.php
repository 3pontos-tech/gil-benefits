<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Observers;

use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

/**
 * Carimba a âncora do ciclo de cota na primeira vez que a assinatura fica ativa.
 *
 * Vive no model, e não em quem escreve, porque existe mais de um caminho de escrita
 * e eles não se conhecem: o Barte entra por `UpsertSubscription`, o Stripe entra pelo
 * `WebhookController` do Cashier — que grava direto na tabela — e ainda há criação por
 * comando de console e por factory. Um carimbo em qualquer um desses pontos deixaria os
 * outros sem âncora, e a coluna existe justamente para não depender do `created_at`.
 *
 * A data nunca é remarcada: é o dia em que a cota da pessoa vira, então um retorno de
 * inadimplência não pode empurrar o ciclo dela para frente.
 */
class SubscriptionQuotaAnchorObserver
{
    public function saving(Subscription $subscription): void
    {
        if ($subscription->quota_anchor_at !== null) {
            return;
        }

        if ($subscription->stripe_status !== Subscription::STATUS_ACTIVE) {
            return;
        }

        $subscription->quota_anchor_at = now();
    }
}
