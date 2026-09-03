<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsSubscriptionCancellation;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Contracts\SupportsCreditPurchase;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Events\CreditOrderPlaced;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuApiException;
use TresPontosTech\IntegrationVirtu\Testing\FakeVirtuClient;
use TresPontosTech\IntegrationVirtu\VirtuAdapter;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    config([
        'virtu.subscription_methods' => ['CREDIT_CARD'],
        'virtu.order_methods' => ['PIX', 'CREDIT_CARD'],
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

it('sells credits through a local order', function (): void {
    expect($this->adapter)->toBeInstanceOf(SupportsCreditPurchase::class);

    $company = Company::factory()->create();

    $url = $this->adapter->purchaseCredits(
        billable: $company,
        company: $company,
        quantity: 2,
        successUrl: 'https://app.test/credits',
        cancelUrl: 'https://app.test/credits',
    );

    $order = CreditOrder::query()->sole();

    expect($url)->toStartWith('https://')
        ->and($order->provider)->toBe(BillingProviderEnum::Virtu)
        ->and($order->quantity)->toBe(2)
        ->and($order->amount_cents)->toBe(30000)
        ->and($order->status)->toBe(CreditOrderStatusEnum::Pending)
        ->and($order->checkout_id)->not->toBeNull();
});

it('leaves the writing to billing and only announces the order', function (): void {
    Event::fake([CreditOrderPlaced::class]);

    $company = Company::factory()->create();

    $this->adapter->purchaseCredits(
        billable: $company,
        company: $company,
        quantity: 2,
        successUrl: 'https://app.test/credits',
        cancelUrl: 'https://app.test/credits',
    );

    expect(CreditOrder::query()->count())->toBe(0);

    Event::assertDispatched(CreditOrderPlaced::class, function (CreditOrderPlaced $event) use ($company): bool {
        return $event->dto->provider === BillingProviderEnum::Virtu
            && $event->dto->billable->is($company)
            && $event->dto->quantity === 2
            && $event->dto->checkoutId === 'checkout_fake1';
    });
});

it('does not open an order when the gateway refuses the link', function (): void {
    $this->client->shouldFail = true;

    $company = Company::factory()->create();

    expect(fn (): string => $this->adapter->purchaseCredits(
        billable: $company,
        company: $company,
        quantity: 2,
        successUrl: 'https://app.test/credits',
        cancelUrl: 'https://app.test/credits',
    ))->toThrow(VirtuApiException::class);

    expect(CreditOrder::query()->count())->toBe(0);
});

// DELETE on a payment link only works while it is unpaid, so an active
// subscription can only be cancelled in the Virtu panel.
it('does not claim to cancel subscriptions', function (): void {
    expect($this->adapter)->not->toBeInstanceOf(SupportsSubscriptionCancellation::class);
});

it('still honours the core billing contract', function (): void {
    expect($this->adapter)->toBeInstanceOf(BillingContract::class);
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

it('refuses a subscription checkout whose link exposes no checkout reference', function (): void {
    Event::fake([SubscriptionCreated::class]);

    // checkoutId is parsed out of the url, so a url without a path segment
    // yields none — and a webhook could never be matched back to this billable.
    $this->client->linkUrl = 'https://checkout.pagaa.com.br';

    expect(fn (): string => $this->adapter->createCheckout($this->user, virtuCheckoutData()))
        ->toThrow(VirtuApiException::class);

    Event::assertNotDispatched(SubscriptionCreated::class);
});

it('refuses a credit purchase whose link exposes no checkout reference', function (): void {
    Event::fake([CreditOrderPlaced::class]);

    $this->client->linkUrl = 'https://checkout.pagaa.com.br';
    $company = Company::factory()->create();

    expect(fn (): string => $this->adapter->purchaseCredits(
        $this->user, $company, 1, 'https://app.test/ok', 'https://app.test/cancel'
    ))->toThrow(VirtuApiException::class);

    Event::assertNotDispatched(CreditOrderPlaced::class);
});
