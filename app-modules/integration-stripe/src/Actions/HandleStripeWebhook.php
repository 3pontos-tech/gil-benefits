<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Subscription as StripeSubscription;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCancelled;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionDefaulted;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Credits\Events\OrderCreditPurchased;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\IntegrationStripe\DTO\CheckoutSessionDTO;
use TresPontosTech\IntegrationStripe\DTO\StripeWebhookDTO;
use TresPontosTech\IntegrationStripe\Enums\StripeWebhookEventEnum;
use TresPontosTech\IntegrationStripe\StripeApi;

/**
 * Turns a Stripe event into the billing domain events. The event is only a notice: the
 * subscription or checkout session is read again from the API, so a redelivered or
 * out-of-order event can never write a stale status.
 */
final readonly class HandleStripeWebhook
{
    public function __construct(
        private StripeApi $api,
    ) {}

    public function handle(StripeWebhookDTO $dto): void
    {
        match (StripeWebhookEventEnum::tryFrom($dto->type)) {
            StripeWebhookEventEnum::SubscriptionCreated,
            StripeWebhookEventEnum::SubscriptionUpdated,
            StripeWebhookEventEnum::SubscriptionDeleted => $this->syncSubscription($dto->objectId, $dto->createdAt),
            StripeWebhookEventEnum::CheckoutCompleted,
            StripeWebhookEventEnum::CheckoutAsyncPaymentSucceeded => $this->checkoutCompleted($dto),
            null => Log::info('Stripe webhook ignorado: evento sem tratamento.', ['type' => $dto->type]),
        };
    }

    /**
     * A subscription checkout only moves the row to its `sub_…` id here; access follows the
     * subscription status, because a Pix payment may still be pending when the session completes.
     */
    private function checkoutCompleted(StripeWebhookDTO $dto): void
    {
        $session = $this->api->retrieveCheckoutSession($dto->objectId);

        if ($session->mode === 'subscription' && is_string($session->subscription)) {
            $this->syncSubscription($session->subscription, $dto->createdAt);

            return;
        }

        if ($session->mode === 'payment') {
            $this->creditOrderPaid($session);
        }
    }

    private function creditOrderPaid(Session $session): void
    {
        if ($session->payment_status !== 'paid') {
            Log::info('Stripe: compra de crédito ainda não paga.', ['session_id' => $session->id]);

            return;
        }

        $order = CreditOrder::query()
            ->where('provider', BillingProviderEnum::Stripe)
            ->where('checkout_id', $session->id)
            ->first();

        if (! $order instanceof CreditOrder) {
            Log::warning('Stripe: pagamento sem pedido de crédito correspondente.', ['session_id' => $session->id]);

            return;
        }

        event(new OrderCreditPurchased(creditOrderId: $order->getKey()));
    }

    private function syncSubscription(string $subscriptionId, int $occurredAt): void
    {
        $stripeSubscription = $this->api->retrieveSubscription($subscriptionId);
        $reference = $stripeSubscription->metadata[CheckoutSessionDTO::REFERENCE_KEY] ?? null;
        $row = $this->findRow($subscriptionId, is_string($reference) ? $reference : null);

        if (! $row instanceof Subscription) {
            Log::warning('Stripe: assinatura sem linha correspondente.', ['subscription_id' => $subscriptionId]);

            return;
        }

        $event = match ($stripeSubscription->status) {
            'active', 'trialing' => new SubscriptionActivated($this->dto(
                $row,
                $stripeSubscription,
                'active',
                $this->scheduledEnd($stripeSubscription),
                $this->timestamp($occurredAt),
            )),
            'past_due', 'unpaid' => new SubscriptionDefaulted($this->dto($row, $stripeSubscription, Subscription::STATUS_DEFAULTER, null)),
            'canceled', 'incomplete_expired' => new SubscriptionCancelled($this->dto(
                $row,
                $stripeSubscription,
                'inactive',
                $this->timestamp($stripeSubscription->ended_at ?? $occurredAt),
            )),
            'incomplete' => new SubscriptionCreated($this->dto($row, $stripeSubscription, 'pending', null)),
            default => null,
        };

        if ($event === null) {
            Log::warning('Stripe: status de assinatura sem mapeamento.', [
                'subscription_id' => $subscriptionId,
                'status' => $stripeSubscription->status,
            ]);

            return;
        }

        event($event);
    }

    private function findRow(string $subscriptionId, ?string $reference): ?Subscription
    {
        $row = Subscription::query()->where('stripe_id', $subscriptionId)->first();

        if ($row instanceof Subscription || $reference === null) {
            return $row;
        }

        return Subscription::query()->where('stripe_id', $reference)->first();
    }

    private function dto(
        Subscription $row,
        StripeSubscription $stripeSubscription,
        string $status,
        ?Carbon $endsAt,
        ?Carbon $activatedAt = null,
    ): SubscriptionDTO {
        return new SubscriptionDTO(
            billableType: $row->subscriptionable_type,
            billableId: $row->subscriptionable_id,
            subscriptionExternalId: $stripeSubscription->id,
            status: $status,
            planExternalId: $row->stripe_price,
            planSlug: $row->type,
            quantity: $row->quantity ?? 1,
            endsAt: $endsAt,
            activatedAt: $activatedAt,
            checkoutReference: $row->stripe_id === $stripeSubscription->id ? null : $row->stripe_id,
        );
    }

    /**
     * A subscription cancelled at period end stays `active` with the date it will stop. Since
     * API version 2025-03-31 the period lives on the subscription item, not on the subscription.
     */
    private function scheduledEnd(StripeSubscription $subscription): ?Carbon
    {
        if ($subscription->cancel_at_period_end) {
            $periodEnd = $subscription->items->data[0]->current_period_end ?? null;

            return is_int($periodEnd) ? $this->timestamp($periodEnd) : null;
        }

        return is_int($subscription->cancel_at) ? $this->timestamp($subscription->cancel_at) : null;
    }

    private function timestamp(int $timestamp): Carbon
    {
        return Date::createFromTimestamp($timestamp)->setTimezone(config('app.timezone'));
    }
}
