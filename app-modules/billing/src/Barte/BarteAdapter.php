<?php

namespace TresPontosTech\Billing\Barte;

use App\Models\Users\User;
use TresPontosTech\App\Filament\Pages\UserBillingManagePage;
use TresPontosTech\Billing\Barte\DTOs\CreateBuyerDto;
use TresPontosTech\Billing\Barte\DTOs\CreatePaymentLinkDto;
use TresPontosTech\Billing\Barte\DTOs\PaymentSubscriptionDto;
use TresPontosTech\Billing\Core\Actions\CreateBillingCustomer;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\DTOs\CreateBillingCustomerDto;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Pages\BillingManagePage;
use TresPontosTech\Company\Models\Company;

final readonly class BarteAdapter implements BillingContract
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

        if ($billable instanceof User && blank($billable?->detail)) {
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
            ->where('stripe_price', 'like', $planUuid . '%')
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

    public function hasActivePlan(Company $company): bool
    {
        return $this->hasActiveSubscription($company);
    }

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        $customerId = $this->findCustomer($billable);

        $price = Price::query()
            ->where('provider_price_id', $data->priceId)
            ->with('plan')
            ->firstOrFail();

        $cycleType = str($data->priceId)->afterLast('-')->upper()->toString();
        $planUuid = $price->plan->provider_product_id;

        $valuePerMonth = $data->isMetered
            ? $this->pricePerSeat($data->quantity) * $data->quantity
            : $price->unit_amount_decimal / 100;

        $response = $this->client->createPaymentLink(new CreatePaymentLinkDto(
            uuidSellerClient: $customerId,
            paymentSubscription: new PaymentSubscriptionDto(
                idPlan: $planUuid,
                valuePerMonth: $valuePerMonth,
                type: $cycleType,
            ),
            scheduledDate: now()->addDay()->toDateString(),
            metadata: [
                ['key' => 'billable_type', 'value' => $billable->getMorphClass()],
                ['key' => 'billable_id', 'value' => (string) $billable->getKey()],
                ['key' => 'barte_plan_uuid', 'value' => $planUuid],
                ['key' => 'barte_cycle_type', 'value' => $cycleType],
                ['key' => 'quantity', 'value' => $data->quantity],
            ],
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

    private function pricePerSeat(int $quantity): float
    {
        return match (true) {
            $quantity <= 15 => 44.90,
            $quantity <= 30 => 34.90,
            $quantity <= 70 => 24.90,
            default => 11.90,
        };
    }

    private function findCustomer(Company|User $billable): ?string
    {
        return BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Barte);

    }

    public function getBillingPortalUrl(User|Company $billable, string $returnUrl, array $options = []): string
    {
        if ($billable instanceof Company) {
            return BillingManagePage::getUrl(tenant: $billable);
        }

        return UserBillingManagePage::getUrl();
    }
}
