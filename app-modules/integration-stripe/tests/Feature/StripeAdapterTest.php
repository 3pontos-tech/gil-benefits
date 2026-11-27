<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Contracts\SupportsCheckoutDiscount;
use TresPontosTech\Billing\Core\Contracts\SupportsLimitedDurationDiscount;
use TresPontosTech\Billing\Core\Contracts\SupportsSubscriptionCancellation;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Contracts\SupportsCreditPurchase;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\IntegrationStripe\Exceptions\StripeCheckoutException;
use TresPontosTech\IntegrationStripe\StripeAdapter;
use TresPontosTech\IntegrationStripe\Testing\FakeStripeApi;

beforeEach(function (): void {
    config([
        'stripe.currency' => 'brl',
        'stripe.pix_max_amount_cents' => 1_500_000,
        'stripe.portal_configurations' => ['company' => 'bpc_company', 'user' => 'bpc_user'],
        'credits.price_in_cents' => 15_000,
    ]);

    $this->api = new FakeStripeApi;
    $this->adapter = new StripeAdapter($this->api);
    $this->user = User::factory()->create();

    $userPlan = Plan::factory()->create(['name' => 'Gold', 'slug' => 'stripe-gold', 'provider' => BillingProviderEnum::Stripe]);
    Price::factory()->for($userPlan, 'plan')->create(['provider_price_id' => 'price_gold', 'unit_amount_decimal' => 17_990]);

    $companyPlan = Plan::factory()->create(['name' => 'Empresa', 'slug' => 'stripe-company', 'provider' => BillingProviderEnum::Stripe]);
    Price::factory()->for($companyPlan, 'plan')->create(['provider_price_id' => 'stripe-company-monthly-subsidized-0', 'unit_amount_decimal' => 0]);
});

function stripeCheckoutData(array $overrides = []): CheckoutData
{
    return new CheckoutData(
        planSlug: $overrides['planSlug'] ?? 'stripe-gold',
        priceId: $overrides['priceId'] ?? 'price_gold',
        isMetered: $overrides['isMetered'] ?? false,
        quantity: $overrides['quantity'] ?? 1,
        trialDays: null,
        allowPromotionCodes: false,
        collectTaxIds: false,
        successUrl: 'https://app.test/ok',
        cancelUrl: 'https://app.test/cancel',
        discount: $overrides['discount'] ?? null,
    );
}

function stripeCompanyCheckoutData(int $seats): CheckoutData
{
    return stripeCheckoutData([
        'planSlug' => 'stripe-company',
        'priceId' => 'stripe-company-monthly-subsidized-0',
        'isMetered' => true,
        'quantity' => $seats,
    ]);
}

it('declares every capability the gateway offers', function (): void {
    expect($this->adapter)
        ->toBeInstanceOf(SupportsSubscriptionCancellation::class)
        ->toBeInstanceOf(SupportsCreditPurchase::class)
        ->toBeInstanceOf(SupportsCheckoutDiscount::class)
        ->toBeInstanceOf(SupportsLimitedDurationDiscount::class);
});

it('sells a person the catalog price and records a pending row keyed by the checkout reference', function (): void {
    $url = $this->adapter->createCheckout($this->user, stripeCheckoutData());

    $session = $this->api->checkoutSessions[0];
    $row = Subscription::query()->sole();
    expect($url)->toBe('https://checkout.stripe.test/cs_fake2')
        ->and($session->mode)->toBe('subscription')
        ->and($session->lineItem->priceId)->toBe('price_gold')
        ->and($session->lineItem->quantity)->toBe(1)
        ->and($session->reference)->toBe($row->stripe_id)
        ->and($row->stripe_status)->toBe('pending')
        ->and($row->stripe_price)->toBe('price_gold')
        ->and($row->type)->toBe('stripe-gold')
        ->and($row->subscriptionable_id)->toBe($this->user->getKey());
});

it('charges a company its seat tier inline', function (int $seats, int $unitAmountCents): void {
    $company = Company::factory()->create();

    $this->adapter->createCheckout($company, stripeCompanyCheckoutData($seats));

    $lineItem = $this->api->checkoutSessions[0]->lineItem;
    expect($lineItem->unitAmountCents)->toBe($unitAmountCents)
        ->and($lineItem->monthly)->toBeTrue()
        ->and($lineItem->quantity)->toBe($seats);
})->with([
    '5 seats' => [5, 4_490],
    '16 seats' => [16, 3_490],
    '31 seats' => [31, 2_490],
    '71 seats' => [71, 1_190],
]);

it('caps the Pix mandate at the full price, not the discounted one', function (): void {
    $this->adapter->createCheckout($this->user, stripeCheckoutData([
        'discount' => new CheckoutDiscount(percentOff: 50, durationCycles: 3),
    ]));

    $session = $this->api->checkoutSessions[0];
    expect($session->acceptsPix)->toBeTrue()
        ->and($session->pixMandateCents)->toBe(17_990);
});

it('offers card only above the Pix single-payment limit', function (): void {
    $company = Company::factory()->create();
    config(['stripe.pix_max_amount_cents' => 100_000]);

    $this->adapter->createCheckout($company, stripeCompanyCheckoutData(100));

    $session = $this->api->checkoutSessions[0];
    expect($session->acceptsPix)->toBeFalse()
        ->and($session->pixMandateCents)->toBeNull();
});

it('mirrors our discount in a coupon applied to the session', function (): void {
    $this->adapter->createCheckout($this->user, stripeCheckoutData([
        'discount' => new CheckoutDiscount(percentOff: 30, durationCycles: 3),
    ]));

    $coupon = $this->api->coupons[0];
    expect($coupon->percentOff)->toBe(30)
        ->and($coupon->durationInMonths)->toBe(3)
        ->and($coupon->currency)->toBe('brl')
        ->and($this->api->checkoutSessions[0]->couponId)->toBe('coupon_fake2');
});

it('records the discount on the pending row', function (): void {
    $this->adapter->createCheckout($this->user, stripeCheckoutData(['discount' => new CheckoutDiscount(percentOff: 30)]));

    expect(Subscription::query()->sole()->discount_percent)->toBe(30);
});

it('refuses a discount that leaves less than the minimum charge', function (): void {
    expect(fn (): string => $this->adapter->createCheckout($this->user, stripeCheckoutData([
        'discount' => new CheckoutDiscount(amountOffCents: 17_950),
    ])))->toThrow(StripeCheckoutException::class);

    expect($this->api->checkoutSessions)->toBe([])
        ->and(Subscription::query()->count())->toBe(0);
});

it('creates the customer once and reuses it', function (): void {
    $this->adapter->createCheckout($this->user, stripeCheckoutData());
    $this->adapter->createCheckout($this->user, stripeCheckoutData());

    expect($this->api->customers)->toHaveCount(1)
        ->and(BillingCustomer::getProviderCustomerId($this->user, BillingProviderEnum::Stripe))->toBe('cus_fake1')
        ->and($this->api->checkoutSessions[1]->customerId)->toBe('cus_fake1');
});

it('does not call Stripe when the panels ask to ensure a customer', function (): void {
    $this->adapter->ensureCustomerExists($this->user);

    expect($this->api->customers)->toBe([]);
});

it('sells credits inline at the configured price through a local order', function (): void {
    $company = Company::factory()->create();

    $url = $this->adapter->purchaseCredits($company, $company, 2, 'https://app.test/credits', 'https://app.test/credits');

    $session = $this->api->checkoutSessions[0];
    $order = CreditOrder::query()->sole();
    expect($url)->toBe('https://checkout.stripe.test/cs_fake2')
        ->and($session->mode)->toBe('payment')
        ->and($session->lineItem->unitAmountCents)->toBe(15_000)
        ->and($session->lineItem->quantity)->toBe(2)
        ->and($session->lineItem->monthly)->toBeFalse()
        ->and($session->acceptsPix)->toBeTrue()
        ->and($session->pixMandateCents)->toBeNull()
        ->and($order->provider)->toBe(BillingProviderEnum::Stripe)
        ->and($order->amount_cents)->toBe(30_000)
        ->and($order->status)->toBe(CreditOrderStatusEnum::Pending)
        ->and($order->checkout_id)->toBe('cs_fake2');
});

it('shows the panel billing page to someone with no Stripe customer', function (): void {
    $portal = $this->adapter->billingPortal($this->user, 'https://app.test/back');

    expect($portal->isExternal())->toBeFalse()
        ->and($this->api->portalSessions)->toBe([]);
});

it('opens the portal configured for each kind of payer', function (string $kind, string $configuration): void {
    $billable = $kind === 'company' ? Company::factory()->create() : $this->user;
    BillingCustomer::query()->create([
        'billable_type' => $billable->getMorphClass(),
        'billable_id' => $billable->getKey(),
        'provider' => BillingProviderEnum::Stripe,
        'provider_customer_id' => 'cus_existing',
    ]);

    $portal = $this->adapter->billingPortal($billable, 'https://app.test/back');

    expect($portal->url)->toBe('https://billing.stripe.test/cus_existing')
        ->and($this->api->portalSessions[0]['configuration'])->toBe($configuration);
})->with([
    'company' => ['company', 'bpc_company'],
    'person' => ['user', 'bpc_user'],
]);

it('cancels the Stripe subscriptions at period end and leaves the rest alone', function (): void {
    $subscription = fn (string $id, string $status) => $this->user->subscriptions()->create([
        'type' => 'stripe-gold',
        'stripe_id' => $id,
        'stripe_status' => $status,
        'quantity' => 1,
    ]);
    $subscription('sub_live', 'active');
    $subscription('sub_old', 'inactive');
    $subscription('checkout_pending', 'pending');

    $this->adapter->cancelSubscription($this->user);

    expect($this->api->cancelledAtPeriodEnd)->toBe(['sub_live'])
        ->and(Subscription::query()->where('stripe_id', 'sub_live')->value('stripe_status'))->toBe('active');
});
