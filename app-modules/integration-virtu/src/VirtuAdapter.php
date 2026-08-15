<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Enums\SeatPricingTierEnum;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Pages\BillingManagePage;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\IntegrationVirtu\DTO\CheckoutIdentityDTO;
use TresPontosTech\IntegrationVirtu\DTO\CreatePaymentLinkDTO;
use TresPontosTech\IntegrationVirtu\Enums\VirtuIntervalEnum;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuApiException;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuUnsupportedOperationException;
use TresPontosTech\PanelApp\Filament\Pages\UserBillingManagePage;

/**
 * Virtu implementation of BillingContract.
 *
 * Only checkout creation talks to the gateway. Subscription state is read from
 * billing_subscriptions — no regression, since the Barte adapter never asked
 * Barte for it either.
 */
final readonly class VirtuAdapter implements BillingContract
{
    /**
     * Floor for any generated charge. Virtu accepts amountCents: 1 without
     * complaint, so the guard the gateway does not provide lives here.
     */
    private const MINIMUM_CHARGE_IN_CENTS = 100;

    public function __construct(
        private VirtuClient $client
    ) {}

    /**
     * No-op: Virtu has no customer resource. The buyer record is created by the
     * hosted checkout from the form, and identity travels there as query params
     * instead — see CheckoutIdentityDTO.
     */
    public function ensureCustomerExists(Company|User $billable): void {}

    public function isSubscribed(Company|User $billable, string $planSlug): bool
    {
        $priceIds = Price::query()
            ->whereHas('plan', fn (Builder $query) => $query->where('slug', $planSlug))
            ->pluck('provider_price_id');

        if ($priceIds->isEmpty()) {
            return false;
        }

        return $this->activeSubscriptions($billable)
            ->whereIn('stripe_price', $priceIds)
            ->exists();
    }

    public function hasActiveSubscription(Company|User $billable): bool
    {
        return $this->activeSubscriptions($billable)->exists();
    }

    public function hasActivePlan(Company $company): bool
    {
        return $this->hasActiveSubscription($company);
    }

    /**
     * Creates the hosted link and records a pending subscription keyed by the
     * checkout reference.
     *
     * That row is the correlation: Virtu accepts no metadata and returns no
     * customer id, so the reference is the only thing the webhook and this call
     * have in common. UpsertSubscription already keys on stripe_id, so the
     * webhook flips this same row to active rather than inserting a second one.
     */
    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        $price = Price::query()
            ->where('provider_price_id', $data->priceId)
            ->with('plan')
            ->firstOrFail();

        $link = $this->client->createPaymentLink(CreatePaymentLinkDTO::subscription(
            title: $price->plan->name,
            amountCents: $this->resolveAmountInCents($price, $data),
            interval: VirtuIntervalEnum::Monthly,
            trialDays: $data->trialDays,
        ));

        event(new SubscriptionCreated(new SubscriptionDTO(
            billableType: $billable->getMorphClass(),
            billableId: $billable->getKey(),
            subscriptionExternalId: $link->checkoutId ?? $link->id,
            status: 'pending',
            planExternalId: $price->provider_price_id,
            planSlug: $price->plan->slug,
            quantity: $data->quantity,
            endsAt: null,
        )));

        return $this->withBuyerIdentity($link->url, $billable);
    }

    /**
     * Not implemented on purpose. A credit purchase has to tell the webhook which
     * company to credit and how many credits — data that rode along in Barte's
     * checkout metadata. Virtu has no metadata field, and unlike a subscription
     * there is no row to hang it on, so there is nowhere for it to travel yet.
     *
     * Credits keep going through Barte, which stays the only checkout provider.
     */
    public function purchaseCredits(Company|User $billable, Company $company, int $quantity, string $successUrl, string $cancelUrl): string
    {
        throw new VirtuUnsupportedOperationException(
            'Virtu cannot carry the company and quantity a credit purchase needs back to the webhook. Use Barte for credits until Pagaa exposes a metadata field.'
        );
    }

    public function checkoutOpensInNewTab(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function getBillingPortalUrl(Company|User $billable, string $returnUrl, array $options = []): string
    {
        if ($billable instanceof Company) {
            return BillingManagePage::getUrl(tenant: $billable);
        }

        return UserBillingManagePage::getUrl();
    }

    /**
     * Virtu offers no way to cancel an active subscription: DELETE on a payment
     * link only works while it is unpaid. Cancelling has to happen in the Virtu
     * panel, so this fails loudly rather than reporting a success that did not
     * happen.
     */
    public function cancelSubscription(Company|User $billable): void
    {
        throw new VirtuUnsupportedOperationException(
            'Virtu does not expose subscription cancellation via API. Cancel it in the Virtu panel (Financeiro → Assinaturas).'
        );
    }

    /**
     * Metered plans price per seat and multiply; everything else charges the price
     * row as stored. pricePerSeat() is in reais, so it is converted here — the
     * REST API is cents-only, while webhooks report reais as strings.
     */
    private function resolveAmountInCents(Price $price, CheckoutData $data): int
    {
        $amountCents = $data->isMetered
            ? (int) round(SeatPricingTierEnum::fromQuantity($data->quantity)->pricePerSeat() * $data->quantity * 100)
            : $price->unit_amount_decimal;

        throw_if(
            $amountCents < self::MINIMUM_CHARGE_IN_CENTS,
            VirtuApiException::class,
            sprintf('Refusing to create a Virtu link for %d cents.', $amountCents),
            0,
            false,
        );

        return $amountCents;
    }

    /**
     * @return Builder<Subscription>
     */
    private function activeSubscriptions(Company|User $billable): Builder
    {
        return Subscription::query()
            ->where('subscriptionable_type', $billable->getMorphClass())
            ->where('subscriptionable_id', $billable->getKey())
            ->where('stripe_status', 'active');
    }

    private function withBuyerIdentity(string $url, Company|User $billable): string
    {
        $params = CheckoutIdentityDTO::fromBillable($billable)->toQueryParams();

        if ($params === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params);
    }
}
