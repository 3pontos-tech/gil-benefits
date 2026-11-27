<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\IntegrationStripe\StripeApi;
use TresPontosTech\IntegrationStripe\Testing\FakeStripeApi;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    config(['stripe.currency' => 'brl']);
    $this->api = new FakeStripeApi;
    $this->app->instance(StripeApi::class, $this->api);
});

it('creates a person plan in our tables and its catalog price in Stripe', function (): void {
    artisan('stripe:plan:create', ['slug' => 'gold', 'name' => 'Flamma Gold', 'amount' => 17_990])->assertSuccessful();

    $plan = Plan::query()->sole();
    $price = Price::query()->sole();
    expect($plan->provider)->toBe(BillingProviderEnum::Stripe)
        ->and($plan->type)->toBe(BillableTypeEnum::User)
        ->and($plan->provider_product_id)->toBe('prod_fake1')
        ->and($price->provider_price_id)->toBe('price_fake2')
        ->and($price->unit_amount_decimal)->toBe(17_990)
        ->and($price->audience)->toBe(PriceAudienceEnum::Standalone)
        ->and($this->api->prices[0])->toMatchArray([
            'product' => 'prod_fake1',
            'currency' => 'brl',
            'unit_amount' => 17_990,
        ]);
});

it('keeps the price when the amount did not change', function (): void {
    artisan('stripe:plan:create', ['slug' => 'gold', 'name' => 'Flamma Gold', 'amount' => 17_990]);

    artisan('stripe:plan:create', ['slug' => 'gold', 'name' => 'Flamma Gold', 'amount' => 17_990])->assertSuccessful();

    expect($this->api->products)->toHaveCount(1)
        ->and($this->api->prices)->toHaveCount(1)
        ->and(Price::query()->count())->toBe(1);
});

it('creates a new price and retires the old row when the amount changes', function (): void {
    artisan('stripe:plan:create', ['slug' => 'gold', 'name' => 'Flamma Gold', 'amount' => 17_990]);

    artisan('stripe:plan:create', ['slug' => 'gold', 'name' => 'Flamma Gold', 'amount' => 19_990])->assertSuccessful();

    expect($this->api->products)->toHaveCount(1)
        ->and(Price::query()->where('active', true)->sole()->unit_amount_decimal)->toBe(19_990)
        ->and(Price::query()->where('active', false)->sole()->unit_amount_decimal)->toBe(17_990);
});

it('creates a company plan without touching Stripe, since seats are priced inline', function (): void {
    artisan('stripe:plan:create', [
        'slug' => 'empresa',
        'name' => 'Flamma Empresas',
        'amount' => 4_490,
        '--type' => 'company',
        '--audience' => 'subsidized',
    ])->assertSuccessful();

    expect($this->api->products)->toBe([])
        ->and($this->api->prices)->toBe([])
        ->and(Plan::query()->sole()->type)->toBe(BillableTypeEnum::Company)
        ->and(Price::query()->sole()->provider_price_id)->toBe('empresa-monthly-subsidized-4490');
});

it('refuses an unknown audience', function (): void {
    artisan('stripe:plan:create', ['slug' => 'gold', 'name' => 'Gold', 'amount' => 100, '--audience' => 'vip'])->assertFailed();

    expect(Plan::query()->count())->toBe(0);
});
