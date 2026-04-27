<?php

namespace TresPontosTech\Billing\Stripe\Subscription;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Company\Models\Company;

class StripeAdapter implements BillingContract
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

        BillingCustomer::query()->create([
            'billable_type' => $billable->getMorphClass(),
            'billable_id' => $billable->getKey(),
            'provider' => BillingProviderEnum::Stripe,
            'provider_customer_id' => $customer->id,
        ]);
    }

    public function isSubscribed(Company|User $billable, string $planSlug): bool
    {
        return $billable->subscribed($planSlug);
    }

    public function hasActivePlan(Company $company): bool
    {
        return $company->hasActivePlan();
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

    public function cancelSubscription(Company|User $billable): void
    {
        $billable->subscription()?->where('stripe_status', 'active')->latest()->first()->cancel();
    }
}
