<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Actions;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCancelled;
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

        if (! $dto->isCancellation()) {
            Log::warning('Virtu: status de assinatura sem mapeamento.', [
                'checkout_id' => $dto->checkoutId,
                'subscription_status' => $dto->subscriptionStatus,
                'previous_status' => $dto->previousSubscriptionStatus,
            ]);

            return;
        }

        event(new SubscriptionCancelled(new SubscriptionDTO(
            billableType: $subscription->subscriptionable_type,
            billableId: $subscription->subscriptionable_id,
            subscriptionExternalId: $subscription->stripe_id,
            status: 'inactive',
            planExternalId: $subscription->stripe_price,
            planSlug: $subscription->type,
            quantity: $subscription->quantity ?? 1,
            endsAt: Date::now(),
        )));
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
