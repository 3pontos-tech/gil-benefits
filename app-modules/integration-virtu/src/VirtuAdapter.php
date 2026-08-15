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
use TresPontosTech\PanelApp\Filament\Pages\UserBillingManagePage;

/**
 * Virtu implementation of BillingContract.
 *
 * Only checkout creation talks to the gateway. Subscription state is read from
 * billing_subscriptions — no regression, since the Barte adapter never asked
 * Barte for it either.
 *
 * Implements neither SupportsSubscriptionCancellation nor SupportsCreditPurchase,
 * and that absence is the feature: Virtu exposes no cancellation endpoint, and a
 * credit purchase has no way to carry the company and quantity back to the
 * webhook without a metadata field. Callers check `instanceof` and hide those
 * actions rather than offering a button that can only fail.
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
