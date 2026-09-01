<?php

namespace TresPontosTech\Billing\Barte;

use App\Models\Users\User;
use TresPontosTech\Billing\Barte\DTOs\CreateBuyerDto;
use TresPontosTech\Billing\Barte\DTOs\CreatePaymentLinkDto;
use TresPontosTech\Billing\Barte\DTOs\PaymentOrderDto;
use TresPontosTech\Billing\Barte\DTOs\PaymentSubscriptionDto;
use TresPontosTech\Billing\Core\Actions\CreateBillingCustomer;
use TresPontosTech\Billing\Core\Actions\Credit\StartCreditOrder;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsCreditPurchase;
use TresPontosTech\Billing\Core\Contracts\SupportsSubscriptionCancellation;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\DTOs\CreateBillingCustomerDto;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\SeatPricingTierEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Pages\BillingManagePage;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\UserBillingManagePage;

final readonly class BarteAdapter implements BillingContract, SupportsCreditPurchase, SupportsSubscriptionCancellation
{
    public function __construct(
        private BarteClient $client
    ) {}

    public function ensureCustomerExists(Company|User $billable): void
    {
        $already = $this->findCustomer($billable);

        if ($already) {
            return;
        }

        if ($billable instanceof User && blank($billable->detail)) {
            return;
        }

        $response = $this->client->createBuyer(CreateBuyerDto::fromBillable($billable));

        resolve(CreateBillingCustomer::class)->handle(
            CreateBillingCustomerDto::make($billable, BillingProviderEnum::Barte, $response['uuid'])
        );
    }

    public function isSubscribed(Company|User $billable, string $planSlug): bool
    {
        if (! $this->findCustomer($billable)) {
            return false;
        }

        $planUuid = Plan::query()
            ->where('slug', $planSlug)
            ->value('provider_product_id');

        if (! $planUuid) {
            return false;
        }

        return Subscription::query()
            ->where('subscriptionable_type', $billable->getMorphClass())
            ->where('subscriptionable_id', $billable->getKey())
            ->where('stripe_status', 'active')
            ->where('stripe_price', $planUuid)
            ->exists();
    }

    public function hasActiveSubscription(Company|User $billable): bool
    {
        $customer = $this->findCustomer($billable);

        if (! $customer) {
            return false;
        }

        return Subscription::query()
            ->where('subscriptionable_type', $billable->getMorphClass())
            ->where('subscriptionable_id', $billable->getKey())
            ->where('stripe_status', 'active')
            ->exists();
    }

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        $customerId = $this->findCustomer($billable);

        $price = Price::query()
            ->where('provider_price_id', $data->priceId)
            ->with('plan')
            ->firstOrFail();

        $planUuid = $price->plan->provider_product_id;

        $valuePerMonth = $data->isMetered
            ? SeatPricingTierEnum::fromQuantity($data->quantity)->pricePerSeat() * $data->quantity
            : $price->unit_amount_decimal / 100;

        $response = $this->client->createPaymentLink(new CreatePaymentLinkDto(
            uuidSellerClient: $customerId,
            scheduledDate: now()->toDateString(),
            metadata: [
                ['key' => 'billable_type', 'value' => $billable->getMorphClass()],
                ['key' => 'billable_id', 'value' => (string) $billable->getKey()],
                ['key' => 'barte_plan_uuid', 'value' => $planUuid],
                ['key' => 'barte_price_id', 'value' => $data->priceId],
                ['key' => 'quantity', 'value' => (string) $data->quantity],
            ],
            paymentSubscription: new PaymentSubscriptionDto(
                uuidPlan: $planUuid,
                valuePerMonth: $valuePerMonth,
            ),
        ));

        return $response['url'];
    }

    public function checkoutOpensInNewTab(): bool
    {
        return true;
    }

    public function cancelSubscription(Company|User $billable): void
    {
        $subscription = Subscription::query()
            ->where('subscriptionable_type', $billable->getMorphClass())
            ->where('subscriptionable_id', $billable->getKey())
            ->where('stripe_status', 'active')
            ->latest()
            ->first();

        if (! $subscription) {
            return;
        }

        $this->client->deleteSubscription($subscription->stripe_id);

    }

    private function findCustomer(Company|User $billable): ?string
    {
        return BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Barte);

    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function getBillingPortalUrl(User|Company $billable, string $returnUrl, array $options = []): string
    {
        if ($billable instanceof Company) {
            return BillingManagePage::getUrl(tenant: $billable);
        }

        return UserBillingManagePage::getUrl();
    }

    public function purchaseCredits(User|Company $billable, Company $company, int $quantity, string $successUrl, string $cancelUrl): string
    {
        $this->ensureCustomerExists($billable);

        $customerId = $this->findCustomer($billable);

        $order = resolve(StartCreditOrder::class)->handle(
            provider: BillingProviderEnum::Barte,
            billable: $billable,
            company: $company,
            quantity: $quantity,
        );

        $response = $this->client->createPaymentLink(new CreatePaymentLinkDto(
            uuidSellerClient: $customerId,
            scheduledDate: now()->toDateString(),
            metadata: [
                ['key' => 'credit_order_id', 'value' => $order->getKey()],
            ],
            type: 'ORDER',
            paymentMethods: ['PIX', 'CREDIT_CARD_EARLY_BUYER'],
            paymentOrder: new PaymentOrderDto(
                title: sprintf('Compra de %d crédito(s)', $quantity),
                value: $order->amount_cents / 100,
                customInstallmentsValues: [
                    ['paymentMethod' => 'PIX', 'installments' => 1],
                    ['paymentMethod' => 'CREDIT_CARD_EARLY_BUYER', 'installments' => 1],
                ],
            ),
        ));

        return $response['url'];
    }
}
