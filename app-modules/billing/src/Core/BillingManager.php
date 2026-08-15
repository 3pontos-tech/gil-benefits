<?php

namespace TresPontosTech\Billing\Core;

use Illuminate\Support\Manager;
use TresPontosTech\Billing\Barte\BarteAdapter;
use TresPontosTech\Billing\Barte\BarteClient;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Stripe\Subscription\StripeAdapter;

class BillingManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return BillingProviderEnum::checkoutCases()[0]->value;
    }

    public function createStripeDriver(): BillingContract
    {
        return new StripeAdapter;
    }

    public function createBarteDriver(): BillingContract
    {
        return new BarteAdapter(new BarteClient);
    }

    public function getDriver(?BillingProviderEnum $provider = null): BillingContract
    {
        $provider ??= BillingProviderEnum::checkoutCases()[0];

        throw_if($provider === BillingProviderEnum::Contractual, \Exception::class, 'To be implemented');

        return $this->driver($provider->value);
    }
}
