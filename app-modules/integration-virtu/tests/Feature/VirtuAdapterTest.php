<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuApiException;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuUnsupportedOperationException;
use TresPontosTech\IntegrationVirtu\Testing\FakeVirtuClient;
use TresPontosTech\IntegrationVirtu\VirtuAdapter;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    config([
        'virtu.subscription_methods' => ['CREDIT_CARD'],
        'virtu.order_methods' => ['PIX', 'CREDIT_CARD'],
        'virtu.max_installments' => 12,
        'virtu.interest_mode' => 'AUTO_TRANSFER',
    ]);

    $this->client = new FakeVirtuClient;
    $this->adapter = new VirtuAdapter($this->client);

    $this->user = User::factory()->create();

    $plan = Plan::factory()->create([
        'name' => 'Gold',
        'slug' => 'virtu-gold',
        'provider' => BillingProviderEnum::Virtu,
    ]);

    $this->price = Price::factory()->for($plan, 'plan')->create([
        'provider_price_id' => 'pp_virtu_gold',
        'unit_amount_decimal' => 25000,
    ]);
});

function virtuCheckoutData(array $overrides = []): CheckoutData
{
    return new CheckoutData(
        planSlug: $overrides['planSlug'] ?? 'virtu-gold',
        priceId: $overrides['priceId'] ?? 'pp_virtu_gold',
        isMetered: $overrides['isMetered'] ?? false,
        quantity: $overrides['quantity'] ?? 1,
        trialDays: $overrides['trialDays'] ?? null,
        allowPromotionCodes: false,
        collectTaxIds: false,
        successUrl: 'https://app.test/ok',
        cancelUrl: 'https://app.test/cancel',
    );
}

it('creates a subscription link and a pending subscription keyed by the checkout reference', function (): void {
    $url = $this->adapter->createCheckout($this->user, virtuCheckoutData());

    expect($url)->toStartWith('https://checkout.pagaa.com.br/checkout/checkout_fake1')
        ->and($this->client->createdLinks[0]['amountCents'])->toBe(25000);

    // The pending row IS the correlation — Virtu accepts no metadata and returns no
    // customer id, so this reference is all the webhook and this call share.
    assertDatabaseHas('billing_subscriptions', [
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'pending',
        'stripe_price' => 'pp_virtu_gold',
        'type' => 'virtu-gold',
        'subscriptionable_id' => $this->user->getKey(),
    ]);
});

it('pre-populates the checkout with the buyer identity', function (): void {
    $company = Company::factory()->recycle($this->user)->create(['tax_id' => '06100023000154']);

    $url = $this->adapter->createCheckout($company, virtuCheckoutData());

    // Virtu creates the buyer from the checkout form, so pre-filling is what makes
    // the webhook's customer.cpf usable as a secondary way to recognise the payer.
    expect($url)->toContain('cpf=06100023000154')
        ->and($url)->toContain(urlencode($company->name))
        ->and($url)->toContain('email=' . urlencode($this->user->email));
});

it('prices metered plans per seat', function (): void {
    $this->adapter->createCheckout($this->user, virtuCheckoutData(['isMetered' => true, 'quantity' => 10]));

    // 10 seats sit in the up-to-15 tier at R$ 44,90 — and pricePerSeat() is in
    // reais while the API is cents-only.
    expect($this->client->createdLinks[0]['amountCents'])->toBe(44900);
});

it('refuses to create a link for an implausible amount', function (): void {
    $this->price->update(['unit_amount_decimal' => 1]);

    // Virtu happily accepts amountCents: 1, so this guard is ours to keep.
    expect(fn (): string => $this->adapter->createCheckout($this->user, virtuCheckoutData()))
        ->toThrow(VirtuApiException::class);
});

it('refuses a credit purchase it could never attribute', function (): void {
    $company = Company::factory()->recycle($this->user)->create();

    // A credit purchase has to tell the webhook which company and how many —
    // Barte carried that in checkout metadata and Virtu has no such field, with no
    // subscription row to hang it on either.
    expect(fn (): string => $this->adapter->purchaseCredits($this->user, $company, 3, 'https://app.test/ok', 'https://app.test/cancel'))
        ->toThrow(VirtuUnsupportedOperationException::class);

    expect($this->client->createdLinks)->toBe([]);
});

it('fails loudly on cancellation, which the API does not support', function (): void {
    expect(fn () => $this->adapter->cancelSubscription($this->user))
        ->toThrow(VirtuUnsupportedOperationException::class);
});

it('reads subscription state locally, without calling the gateway', function (): void {
    expect($this->adapter->hasActiveSubscription($this->user))->toBeFalse()
        ->and($this->adapter->isSubscribed($this->user, 'virtu-gold'))->toBeFalse();

    Subscription::query()->create([
        'subscriptionable_type' => $this->user->getMorphClass(),
        'subscriptionable_id' => $this->user->getKey(),
        'type' => 'virtu-gold',
        'stripe_id' => 'checkout_fake1',
        'stripe_status' => 'active',
        'stripe_price' => 'pp_virtu_gold',
        'quantity' => 1,
    ]);

    expect($this->adapter->hasActiveSubscription($this->user))->toBeTrue()
        ->and($this->adapter->isSubscribed($this->user, 'virtu-gold'))->toBeTrue()
        ->and($this->adapter->isSubscribed($this->user, 'some-other-plan'))->toBeFalse()
        ->and($this->client->createdLinks)->toBe([]);
});

it('is a no-op when asked to ensure a customer exists', function (): void {
    // There is no customer resource to create.
    $this->adapter->ensureCustomerExists($this->user);

    expect($this->client->createdLinks)->toBe([]);
});
