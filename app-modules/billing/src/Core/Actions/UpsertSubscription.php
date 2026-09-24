<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions;

use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

final class UpsertSubscription
{
    /**
     * Grava a linha da assinatura, e a âncora do ciclo quando o provedor disse a hora.
     *
     * O `SubscriptionQuotaAnchorObserver` carimba `quota_anchor_at` com `now()`, que é
     * quando ESTE worker processou o evento — não quando a assinatura passou a valer. Um
     * pagamento aprovado às 23h58 e reprocessado depois da meia-noite faria a pessoa virar
     * no dia seguinte, para sempre. Preenchendo aqui, o observer encontra a coluna cheia e
     * se cala; quem não tem a hora no payload continua caindo nele.
     *
     * Só quando ainda está nula: uma cobrança recorrente cai na MESMA linha, e reescrever
     * empurraria o ciclo de quem já é assinante.
     */
    public function handle(SubscriptionDTO $dto): void
    {
        $subscription = Subscription::query()->firstOrNew(['stripe_id' => $dto->subscriptionExternalId]);

        $subscription->fill([
            'subscriptionable_type' => $dto->billableType,
            'subscriptionable_id' => $dto->billableId,
            'type' => $dto->planSlug,
            'stripe_status' => $dto->status,
            'stripe_price' => $dto->planExternalId,
            'quantity' => $dto->quantity,
            'ends_at' => $dto->endsAt,
        ]);

        if ($dto->activatedAt instanceof Carbon && $subscription->quota_anchor_at === null) {
            $subscription->quota_anchor_at = $dto->activatedAt;
        }

        $subscription->save();
    }
}
