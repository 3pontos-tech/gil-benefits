<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe;

use App\Models\Users\User;
use Illuminate\Support\Str;
use TresPontosTech\Billing\Core\Actions\CreateBillingCustomer;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsCheckoutDiscount;
use TresPontosTech\Billing\Core\Contracts\SupportsLimitedDurationDiscount;
use TresPontosTech\Billing\Core\Contracts\SupportsSubscriptionCancellation;
use TresPontosTech\Billing\Core\DTOs\BillingPortal;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Billing\Core\DTOs\CreateBillingCustomerDto;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\SeatPricingTierEnum;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Contracts\SupportsCreditPurchase;
use TresPontosTech\Credits\DTOs\CreditOrderDTO;
use TresPontosTech\Credits\Events\CreditOrderPlaced;
use TresPontosTech\IntegrationStripe\DTO\CheckoutLineItemDTO;
use TresPontosTech\IntegrationStripe\DTO\CheckoutSessionDTO;
use TresPontosTech\IntegrationStripe\DTO\CouponDTO;
use TresPontosTech\IntegrationStripe\Exceptions\StripeCheckoutException;

/**
 * Stripe implementation of BillingContract, through hosted Checkout and the customer portal.
 *
 * Prices come from our tables, never the other way round: a company pays its seat tier
 * inline, a person pays the catalog price created by `stripe:plan:create`, and credits
 * are charged inline at `credits.price_in_cents`.
 */
final readonly class StripeAdapter implements BillingContract, SupportsCheckoutDiscount, SupportsCreditPurchase, SupportsLimitedDurationDiscount, SupportsSubscriptionCancellation
{
    public function __construct(
        private StripeApi $api,
    ) {}

    /**
     * No-op on purpose: the panels call this on every request, and a Stripe customer is
     * only worth creating for someone who pays. It is created at the first checkout or
     * credit purchase instead.
     */
    public function ensureCustomerExists(Company|User $billable): void {}

    /**
     * Writes the pending row before sending the buyer to Stripe, keyed by a reference that
     * travels in the subscription metadata. The webhook moves that row to the `sub_…` id
     * once Stripe reports the subscription, so a checkout never leaves two rows behind.
     *
     * The Pix Automático mandate caps every charge, so it is the full price, never the
     * discounted one: when the discount ends the next charge must still fit under it.
     */
    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        $price = Price::query()
            ->where('provider_price_id', $data->priceId)
            ->with('plan')
            ->firstOrFail();

        $fullAmountCents = $this->subscriptionAmountInCents($price, $data);
        $reference = 'checkout_' . Str::uuid()->toString();

        $session = $this->api->createCheckoutSession(CheckoutSessionDTO::subscription(
            customerId: $this->customerFor($billable),
            reference: $reference,
            lineItem: $this->subscriptionLineItem($price, $data),
            successUrl: $data->successUrl,
            cancelUrl: $data->cancelUrl,
            pixMandateCents: $this->acceptsPix($fullAmountCents) ? $fullAmountCents : null,
            couponId: $this->couponFor($data->discount, $fullAmountCents),
        ));

        event(new SubscriptionCreated(new SubscriptionDTO(
            billableType: $billable->getMorphClass(),
            billableId: $billable->getKey(),
            subscriptionExternalId: $reference,
            status: 'pending',
            planExternalId: $price->provider_price_id,
            planSlug: $price->plan->slug,
            quantity: $data->quantity,
            endsAt: null,
            discount: $data->discount,
        )));

        return (string) $session->url;
    }

    public function purchaseCredits(Company|User $billable, Company $company, int $quantity, string $successUrl, string $cancelUrl): string
    {
        $order = new CreditOrderDTO(
            provider: BillingProviderEnum::Stripe,
            billable: $billable,
            company: $company,
            quantity: $quantity,
        );

        $session = $this->api->createCheckoutSession(CheckoutSessionDTO::payment(
            customerId: $this->customerFor($billable),
            lineItem: CheckoutLineItemDTO::inline(
                productName: 'Crédito de consultoria',
                unitAmountCents: (int) config('credits.price_in_cents'),
                quantity: $quantity,
                currency: $this->currency(),
                monthly: false,
            ),
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
            acceptsPix: $this->acceptsPix($order->amountCents()),
        ));

        event(new CreditOrderPlaced($order->withCheckout($session->id)));

        return (string) $session->url;
    }

    public function checkoutOpensInNewTab(): bool
    {
        return false;
    }

    /**
     * Someone who never paid through Stripe has no customer there, so there are no invoices
     * or card to manage: the panel shows its own billing page instead of an empty portal.
     */
    public function billingPortal(Company|User $billable, string $returnUrl): BillingPortal
    {
        $customerId = BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Stripe);

        if ($customerId === null) {
            return BillingPortal::internal();
        }

        $configuration = config('stripe.portal_configurations.' . ($billable instanceof Company ? 'company' : 'user'));

        return BillingPortal::external($this->api->createPortalSession($customerId, $returnUrl, $configuration));
    }

    /**
     * Cancels at the end of the paid period. The row stays `active` until the webhook brings
     * the end date, and access lasts until then.
     */
    public function cancelSubscription(Company|User $billable): void
    {
        $billable->subscriptions()
            ->grantingAccess()
            ->pluck('stripe_id')
            ->filter(fn (string $subscriptionId): bool => str_starts_with($subscriptionId, 'sub_'))
            ->each(fn (string $subscriptionId) => $this->api->cancelAtPeriodEnd($subscriptionId));
    }

    private function customerFor(Company|User $billable): string
    {
        $customerId = BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Stripe);

        if ($customerId !== null) {
            return $customerId;
        }

        $customerId = $this->api->createCustomer(array_filter([
            'name' => $billable->name,
            'email' => $billable instanceof User ? $billable->email : null,
            'metadata' => [
                'billable_type' => $billable->getMorphClass(),
                'billable_id' => (string) $billable->getKey(),
            ],
        ]));

        resolve(CreateBillingCustomer::class)->handle(
            CreateBillingCustomerDto::make($billable, BillingProviderEnum::Stripe, $customerId),
        );

        return $customerId;
    }

    private function subscriptionLineItem(Price $price, CheckoutData $data): CheckoutLineItemDTO
    {
        if (! $data->isMetered) {
            return CheckoutLineItemDTO::catalog($price->provider_price_id, $data->quantity);
        }

        return CheckoutLineItemDTO::inline(
            productName: $price->plan->name,
            unitAmountCents: $this->seatPriceInCents($data->quantity),
            quantity: $data->quantity,
            currency: $this->currency(),
            monthly: true,
        );
    }

    private function subscriptionAmountInCents(Price $price, CheckoutData $data): int
    {
        return $data->isMetered
            ? $this->seatPriceInCents($data->quantity) * $data->quantity
            : (int) $price->unit_amount_decimal * $data->quantity;
    }

    private function seatPriceInCents(int $seats): int
    {
        return (int) round(SeatPricingTierEnum::fromQuantity($seats)->pricePerSeat() * 100);
    }

    /**
     * Pix refuses a single payment above its limit, so a larger checkout offers card only.
     */
    private function acceptsPix(int $amountCents): bool
    {
        return $amountCents <= (int) config('stripe.pix_max_amount_cents');
    }

    /**
     * The discount is ours (module `discounts`); Stripe only receives a single-use coupon
     * that mirrors it.
     */
    private function couponFor(?CheckoutDiscount $discount, int $fullAmountCents): ?string
    {
        if (! $discount instanceof CheckoutDiscount) {
            return null;
        }

        throw_if(
            $discount->apply($fullAmountCents) < CheckoutDiscount::MINIMUM_CHARGE_IN_CENTS,
            StripeCheckoutException::class,
            sprintf('Refusing a Stripe checkout discounted below %d cents.', CheckoutDiscount::MINIMUM_CHARGE_IN_CENTS),
        );

        return $this->api->createCoupon(CouponDTO::fromDiscount($discount, $this->currency()));
    }

    private function currency(): string
    {
        return (string) config('stripe.currency');
    }
}
