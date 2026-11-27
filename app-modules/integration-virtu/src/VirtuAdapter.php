<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsCheckoutDiscount;
use TresPontosTech\Billing\Core\DTOs\BillingPortal;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\SeatPricingTierEnum;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Contracts\SupportsCreditPurchase;
use TresPontosTech\Credits\DTOs\CreditOrderDTO;
use TresPontosTech\Credits\Events\CreditOrderPlaced;
use TresPontosTech\IntegrationVirtu\DTO\CheckoutIdentityDTO;
use TresPontosTech\IntegrationVirtu\DTO\CreatePaymentLinkDTO;
use TresPontosTech\IntegrationVirtu\Enums\VirtuIntervalEnum;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuApiException;
use TresPontosTech\IntegrationVirtu\Responses\PaymentLinkResponse;

/**
 * Virtu implementation of BillingContract.
 *
 * Only checkout creation talks to the gateway. Subscription state is read from
 * billing_subscriptions — no regression, since the Barte adapter never asked
 * Barte for it either.
 *
 * Does not implement SupportsSubscriptionCancellation, and that absence is the
 * feature: Virtu exposes no cancellation endpoint, so callers check `instanceof`
 * and hide the action rather than offering a button that can only fail.
 */
final readonly class VirtuAdapter implements BillingContract, SupportsCheckoutDiscount, SupportsCreditPurchase
{
    public function __construct(
        private VirtuClient $client
    ) {}

    /**
     * No-op: Virtu has no customer resource. The buyer record is created by the
     * hosted checkout from the form, and identity travels there as query params
     * instead — see CheckoutIdentityDTO.
     */
    public function ensureCustomerExists(Company|User $billable): void {}

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
            subscriptionExternalId: $this->correlationKey($link),
            status: 'pending',
            planExternalId: $price->provider_price_id,
            planSlug: $price->plan->slug,
            quantity: $data->quantity,
            endsAt: null,
            discount: $data->discount,
        )));

        return $this->withBuyerIdentity($link->url, $billable);
    }

    public function purchaseCredits(Company|User $billable, Company $company, int $quantity, string $successUrl, string $cancelUrl): string
    {
        $order = new CreditOrderDTO(
            provider: BillingProviderEnum::Virtu,
            billable: $billable,
            company: $company,
            quantity: $quantity,
        );

        $link = $this->client->createPaymentLink(CreatePaymentLinkDTO::order(
            title: sprintf('Compra de %d crédito(s)', $quantity),
            amountCents: $order->amountCents(),
            description: sprintf('%d crédito(s) para %s', $quantity, $company->name),
        ));

        event(new CreditOrderPlaced($order->withCheckout($this->correlationKey($link))));

        return $this->withBuyerIdentity($link->url, $billable);
    }

    public function checkoutOpensInNewTab(): bool
    {
        return true;
    }

    public function billingPortal(Company|User $billable, string $returnUrl): BillingPortal
    {
        return BillingPortal::internal();
    }

    /**
     * Metered plans price per seat and multiply; everything else charges the price
     * row as stored. pricePerSeat() is in reais, so it is converted here — the
     * REST API is cents-only, while webhooks report reais as strings.
     */
    /**
     * The webhook only ever reports the checkout reference embedded in the link
     * URL, never the `pl_…` id, so a link we cannot derive it from can never be
     * correlated. Falling back to the id would take the money and leave the
     * pending row unreachable; failing here costs a click instead.
     */
    private function correlationKey(PaymentLinkResponse $link): string
    {
        throw_if(
            blank($link->checkoutId),
            VirtuApiException::class,
            sprintf('Virtu link %s exposes no checkout reference in its url.', $link->id),
            0,
            false,
        );

        return $link->checkoutId;
    }

    private function resolveAmountInCents(Price $price, CheckoutData $data): int
    {
        $amountCents = $data->isMetered
            ? (int) round(SeatPricingTierEnum::fromQuantity($data->quantity)->pricePerSeat() * $data->quantity * 100)
            : $price->unit_amount_decimal;

        if ($data->discount instanceof CheckoutDiscount) {
            throw_if(
                $data->discount->durationCycles !== null,
                VirtuApiException::class,
                'Virtu não aplica desconto em número limitado de cobranças.',
                0,
                false,
            );

            $amountCents = $data->discount->apply($amountCents);
        }

        throw_if(
            $amountCents < CheckoutDiscount::MINIMUM_CHARGE_IN_CENTS,
            VirtuApiException::class,
            sprintf('Refusing to create a Virtu link for %d cents.', $amountCents),
            0,
            false,
        );

        return $amountCents;
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
