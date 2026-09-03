<?php

namespace TresPontosTech\Billing\Stripe\Subscription;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Actions\CreateBillingCustomer;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsCreditPurchase;
use TresPontosTech\Billing\Core\Contracts\SupportsSubscriptionCancellation;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\DTOs\CreateBillingCustomerDto;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

class StripeAdapter implements BillingContract, SupportsCreditPurchase, SupportsSubscriptionCancellation
{
    public function ensureCustomerExists(Company|User $billable): void
    {
        if ($billable->hasStripeId()) {
            return;
        }

        $modelType = $billable instanceof Company ? Company::class : User::class;

        $customer = $billable->createAsStripeCustomer([
            'metadata' => [
                'model_type' => $modelType,
            ],
        ]);

        resolve(CreateBillingCustomer::class)->handle(
            CreateBillingCustomerDto::make($billable, BillingProviderEnum::Stripe, $customer->id)
        );
    }

    public function isSubscribed(Company|User $billable, string $planSlug): bool
    {
        return Subscription::grantsAccess($billable, $planSlug);
    }

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        $builder = $billable->newSubscription(type: $data->planSlug, prices: [$data->priceId]);

        if ($data->isMetered) {
            $builder->meteredPrice($data->priceId)->quantity($data->quantity);
        }

        if ($data->trialDays !== null) {
            $builder->trialDays($data->trialDays);
        }

        if ($data->allowPromotionCodes) {
            $builder->allowPromotionCodes();
        }

        if ($data->collectTaxIds) {
            $builder->collectTaxIds();
        }

        $session = $builder
            ->withMetadata($data->metadata)
            ->checkout([
                'success_url' => $data->successUrl,
                'cancel_url' => $data->cancelUrl,
                'customer_update' => ['address' => 'auto'],
            ]);

        return $session->asStripeCheckoutSession()->url;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function getBillingPortalUrl(Company|User $billable, string $returnUrl, array $options = []): string
    {
        return $billable
            ->redirectToBillingPortal(returnUrl: $returnUrl, options: ['configuration' => config('cashier.portals.company')])
            ->getTargetUrl();
    }

    public function hasActiveSubscription(Company|User $billable): bool
    {
        if ($billable instanceof User) {
            return $billable->activeSubscription()->exists();
        }

        return $billable->subscriptions()
            ->whereIn('stripe_status', ['active', 'incomplete'])
            ->exists();
    }

    public function checkoutOpensInNewTab(): bool
    {
        return false;
    }

    public function cancelSubscription(Company|User $billable): void
    {
        $billable->subscriptions()->where('stripe_status', 'active')->latest()->first()?->cancel();
    }

    public function purchaseCredits(Company|User $billable, Company $company, int $quantity, string $successUrl, string $cancelUrl): string
    {
        $this->ensureCustomerExists($billable);

        /** @var string $priceId */
        $priceId = config('cashier.credits.price_id');

        $ownerId = $billable instanceof User ? $billable->getKey() : $company->owner->getKey();

        $session = $billable->checkout([$priceId => $quantity], [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'type' => 'credits',
                'company_id' => $company->getKey(),
                'owner_id' => $ownerId,
                'quantity' => $quantity,
            ],
        ]);

        return $session->url;
    }
}
