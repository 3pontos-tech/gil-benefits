<?php

use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

/**
 * Fake HTTP client para interceptar chamadas ao Stripe SDK.
 * O SDK usa ApiRequestor internamente, que delega ao _httpClient injetado.
 */
function fakeStripeHttpClient(array $products = [], array $prices = []): ClientInterface
{
    return new class($products, $prices) implements ClientInterface
    {
        public function __construct(
            private readonly array $products,
            private readonly array $prices,
        ) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1'): array
        {
            if (str_contains($absUrl, '/v1/products')) {
                return [
                    json_encode([
                        'object' => 'list',
                        'url' => '/v1/products',
                        'has_more' => false,
                        'data' => $this->products,
                    ]),
                    200,
                    ['Request-Id' => 'req_test_products'],
                ];
            }

            if (str_contains($absUrl, '/v1/prices')) {
                return [
                    json_encode([
                        'object' => 'list',
                        'url' => '/v1/prices',
                        'has_more' => false,
                        'data' => $this->prices,
                    ]),
                    200,
                    ['Request-Id' => 'req_test_prices'],
                ];
            }

            return [json_encode(['object' => 'list', 'data' => [], 'has_more' => false, 'url' => '/']), 200, []];
        }
    };
}

beforeEach(function (): void {
    config(['cashier.secret' => 'sk_test_fake_key_for_tests']);
});

afterEach(function (): void {
    ApiRequestor::setHttpClient(null);
});

it('syncs active stripe products as plans in the database', function (): void {
    ApiRequestor::setHttpClient(fakeStripeHttpClient(
        products: [
            ['id' => 'prod_active_1', 'object' => 'product', 'active' => true, 'name' => 'Gold Plan', 'description' => 'Gold tier'],
        ],
    ));

    $this->artisan('billing:sync-stripe')->assertSuccessful();

    expect(Plan::query()->where('provider_product_id', 'prod_active_1')->exists())->toBeTrue()
        ->and(Plan::query()->where('provider', BillingProviderEnum::Stripe)->count())->toBe(1);
});

it('does not sync inactive stripe products', function (): void {
    ApiRequestor::setHttpClient(fakeStripeHttpClient(
        products: [
            ['id' => 'prod_inactive_1', 'object' => 'product', 'active' => false, 'name' => 'Old Plan', 'description' => 'Old tier'],
        ],
    ));

    $this->artisan('billing:sync-stripe')->assertSuccessful();

    expect(Plan::query()->where('provider_product_id', 'prod_inactive_1')->exists())->toBeFalse();
});

it('syncs active prices for each product', function (): void {
    ApiRequestor::setHttpClient(fakeStripeHttpClient(
        products: [
            ['id' => 'prod_price_test', 'object' => 'product', 'active' => true, 'name' => 'Price Test Plan', 'description' => null],
        ],
        prices: [
            [
                'id' => 'price_active_1',
                'object' => 'price',
                'active' => true,
                'billing_scheme' => 'per_unit',
                'tiers_mode' => null,
                'type' => 'recurring',
                'unit_amount' => 9900,
                'product' => 'prod_price_test',
                'metadata' => (object) [],
            ],
        ],
    ));

    $this->artisan('billing:sync-stripe')->assertSuccessful();

    $plan = Plan::query()->where('provider_product_id', 'prod_price_test')->firstOrFail();
    expect(Price::query()->where('billing_plan_id', $plan->id)->where('provider_price_id', 'price_active_1')->exists())->toBeTrue();
});

it('does not sync inactive prices', function (): void {
    ApiRequestor::setHttpClient(fakeStripeHttpClient(
        products: [
            ['id' => 'prod_inactive_price', 'object' => 'product', 'active' => true, 'name' => 'Inactive Price Plan', 'description' => null],
        ],
        prices: [
            [
                'id' => 'price_inactive_1',
                'object' => 'price',
                'active' => false,
                'billing_scheme' => 'per_unit',
                'tiers_mode' => null,
                'type' => 'recurring',
                'unit_amount' => 5000,
                'product' => 'prod_inactive_price',
                'metadata' => (object) [],
            ],
        ],
    ));

    $this->artisan('billing:sync-stripe')->assertSuccessful();

    $plan = Plan::query()->where('provider_product_id', 'prod_inactive_price')->firstOrFail();
    expect(Price::query()->where('billing_plan_id', $plan->id)->count())->toBe(0);
});

it('is idempotent — running twice does not duplicate plans or prices', function (): void {
    ApiRequestor::setHttpClient(fakeStripeHttpClient(
        products: [
            ['id' => 'prod_idem_1', 'object' => 'product', 'active' => true, 'name' => 'Idempotency Plan', 'description' => null],
        ],
        prices: [
            [
                'id' => 'price_idem_1',
                'object' => 'price',
                'active' => true,
                'billing_scheme' => 'per_unit',
                'tiers_mode' => null,
                'type' => 'recurring',
                'unit_amount' => 1900,
                'product' => 'prod_idem_1',
                'metadata' => (object) [],
            ],
        ],
    ));

    $this->artisan('billing:sync-stripe')->assertSuccessful();
    $this->artisan('billing:sync-stripe')->assertSuccessful();

    expect(Plan::query()->where('provider', BillingProviderEnum::Stripe)->where('provider_product_id', 'prod_idem_1')->count())->toBe(1)
        ->and(Price::query()->where('provider_price_id', 'price_idem_1')->count())->toBe(1);
});

it('syncs slug from product name', function (): void {
    ApiRequestor::setHttpClient(fakeStripeHttpClient(
        products: [
            ['id' => 'prod_slug_test', 'object' => 'product', 'active' => true, 'name' => 'My Premium Plan', 'description' => null],
        ],
    ));

    $this->artisan('billing:sync-stripe')->assertSuccessful();

    expect(Plan::query()->where('slug', 'my-premium-plan')->exists())->toBeTrue();
});

it('defaults synced plan type to User billable type', function (): void {
    ApiRequestor::setHttpClient(fakeStripeHttpClient(
        products: [
            ['id' => 'prod_type_test', 'object' => 'product', 'active' => true, 'name' => 'Type Test Plan', 'description' => null],
        ],
    ));

    $this->artisan('billing:sync-stripe')->assertSuccessful();

    $plan = Plan::query()->where('provider_product_id', 'prod_type_test')->firstOrFail();
    expect($plan->type)->toBe(BillableTypeEnum::User);
});
