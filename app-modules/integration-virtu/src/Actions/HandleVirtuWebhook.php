<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Actions;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCancelled;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionDefaulted;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\IntegrationVirtu\DTO\VirtuWebhookDTO;
use TresPontosTech\IntegrationVirtu\Enums\VirtuWebhookEventEnum;

/**
 * Turns a Virtu webhook into the billing module's provider-agnostic domain
 * events, so persistence stays with SyncSubscriptionOnStatusChange and
 * UpsertSubscription and nothing downstream learns which gateway paid.
 */
final class HandleVirtuWebhook
{
    public function handle(VirtuWebhookDTO $dto): void
    {
        if ($dto->event !== VirtuWebhookEventEnum::Transaction) {
            // REFUND, REFUND_PARTIAL and WITHDRAWAL are recorded by
            // StoreInboundWebhook but have no mapping yet — reversal handling is
            // its own task.
            Log::info('Virtu webhook ignorado: evento sem tratamento.', [
                'event' => $dto->event?->value,
            ]);

            return;
        }

        if ($dto->isSubscriptionStatusChange()) {
            $this->subscriptionStatusChanged($dto);

            return;
        }

        if (! $dto->isPaid()) {
            Log::info('Virtu webhook ignorado: cobrança não aprovada.', [
                'status' => $dto->status,
                'payment_status' => $dto->paymentStatus,
            ]);

            return;
        }

        if ($this->creditOrderPaid($dto)) {
            return;
        }

        $subscription = $this->findPendingSubscription($dto);

        if (! $subscription instanceof Subscription) {
            // With no metadata and no customer id, an unrecognised checkout
            // reference cannot be attributed. Loud but non-fatal: retrying will
            // not help, and the raw payload is already stored for reconciliation.
            Log::warning('Virtu webhook sem assinatura correspondente.', [
                'checkout_id' => $dto->checkoutId,
                'sale_id' => $dto->saleId,
                'customer_tax_id' => $dto->customerTaxId,
            ]);

            return;
        }

        // Same external id, so UpsertSubscription updates the pending row created
        // at checkout instead of inserting a second one. Every later charge in the
        // cycle lands on it too.
        event(new SubscriptionActivated(new SubscriptionDTO(
            billableType: $subscription->subscriptionable_type,
            billableId: $subscription->subscriptionable_id,
            subscriptionExternalId: $subscription->stripe_id,
            status: 'active',
            planExternalId: $subscription->stripe_price,
            planSlug: $subscription->type,
            quantity: $subscription->quantity ?? 1,
            endsAt: null,
            activatedAt: $this->occurredAt($dto),
        )));
    }

    /**
     * A lifecycle change reuses the original sale's checkoutId, so the row is
     * found the same way a charge finds it — only the resulting status differs.
     */
    private function subscriptionStatusChanged(VirtuWebhookDTO $dto): void
    {
        $subscription = $this->findPendingSubscription($dto);

        if (! $subscription instanceof Subscription) {
            Log::warning('Virtu: mudança de status sem assinatura correspondente.', [
                'checkout_id' => $dto->checkoutId,
                'subscription_status' => $dto->subscriptionStatus,
            ]);

            return;
        }

        // Delinquency keeps no endsAt: the subscription is recoverable, and a
        // retry that goes through comes back here as ACTIVE.
        $event = match (true) {
            $dto->isCancellation() => new SubscriptionCancelled(
                $this->subscriptionDTO($subscription, 'inactive', Date::now())
            ),
            $dto->isDelinquent() => new SubscriptionDefaulted(
                $this->subscriptionDTO($subscription, 'defaulter', null)
            ),
            $dto->isReactivation() => new SubscriptionActivated(
                $this->subscriptionDTO($subscription, 'active', null, $this->occurredAt($dto))
            ),
            default => null,
        };

        if ($event === null) {
            Log::warning('Virtu: status de assinatura sem mapeamento.', [
                'checkout_id' => $dto->checkoutId,
                'subscription_status' => $dto->subscriptionStatus,
                'previous_status' => $dto->previousSubscriptionStatus,
            ]);

            return;
        }

        event($event);
    }

    private function subscriptionDTO(
        Subscription $subscription,
        string $status,
        ?Carbon $endsAt,
        ?Carbon $activatedAt = null,
    ): SubscriptionDTO {
        return new SubscriptionDTO(
            billableType: $subscription->subscriptionable_type,
            billableId: $subscription->subscriptionable_id,
            subscriptionExternalId: $subscription->stripe_id,
            status: $status,
            planExternalId: $subscription->stripe_price,
            planSlug: $subscription->type,
            quantity: $subscription->quantity ?? 1,
            endsAt: $endsAt,
            activatedAt: $activatedAt,
        );
    }

    /**
     * O instante que a Virtu carimbou no evento, que vira âncora do ciclo de cota.
     *
     * O webhook é processado em fila e pode ser reentregue: sem isso, uma aprovação das
     * 23h58 processada depois da meia-noite ancoraria a pessoa no dia seguinte, e a data de
     * renovação dela nasceria errada de vez. Payload sem `occurredAt`, ou com data que não
     * dá para ler, devolve nulo e o observer carimba como sempre carimbou.
     *
     * A Virtu manda em UTC e a coluna é lida no fuso do app, então converter não é detalhe:
     * `QuotaCycle` corta a âncora no início do dia, e 02h UTC ainda é a véspera em São Paulo.
     * Guardar o horário de parede errado erra o dia da virada — o mesmo defeito, ao contrário.
     */
    private function occurredAt(VirtuWebhookDTO $dto): ?Carbon
    {
        if (blank($dto->occurredAt)) {
            return null;
        }

        try {
            return Date::parse($dto->occurredAt)->setTimezone(config('app.timezone'));
        } catch (InvalidFormatException) {
            Log::warning('Virtu: occurredAt ilegivel, ancora cai no processamento.', [
                'occurred_at' => $dto->occurredAt,
                'checkout_id' => $dto->checkoutId,
            ]);

            return null;
        }
    }

    private function creditOrderPaid(VirtuWebhookDTO $dto): bool
    {
        if (blank($dto->checkoutId)) {
            return false;
        }

        $order = CreditOrder::query()
            ->where('provider', BillingProviderEnum::Virtu)
            ->where('checkout_id', $dto->checkoutId)
            ->first();

        if (! $order instanceof CreditOrder) {
            return false;
        }

        event(new OrderCreditPurchased(creditOrderId: $order->getKey()));

        return true;
    }

    /**
     * The subscription row written when the checkout was created is the only link
     * between this payload and a billable — createCheckout keys it on the same
     * checkout reference the webhook reports.
     */
    private function findPendingSubscription(VirtuWebhookDTO $dto): ?Subscription
    {
        if (blank($dto->checkoutId)) {
            return null;
        }

        return Subscription::query()
            ->where('stripe_id', $dto->checkoutId)
            ->first();
    }
}
