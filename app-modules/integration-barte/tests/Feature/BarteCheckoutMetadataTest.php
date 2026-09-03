<?php

use App\Models\Users\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\IntegrationBarte\BarteAdapter;
use TresPontosTech\IntegrationBarte\BarteClient;

// Ensures the checkout forwards the chosen price id to Barte so the webhook can persist it
// (flamma's standalone-user price must survive the round-trip).

it('sends the chosen price id to Barte as barte_price_id metadata', function (): void {
    config(['services.barte.base_url' => 'https://api.barte.test']);

    Http::fake([
        '*' => Http::response(['url' => 'https://pay.barte.test/checkout']),
    ]);

    $user = User::factory()->create();
    $plan = Plan::factory()->barte()->active()->create(['provider_product_id' => 'plan-uuid']);

    $price = Price::factory()->for($plan, 'plan')->create([
        'provider_price_id' => 'plan-uuid-standalone-user',
        'unit_amount_decimal' => 25000,
        'audience' => PriceAudienceEnum::Standalone,
    ]);

    BillingCustomer::factory()->create([
        'billable_type' => $user->getMorphClass(),
        'billable_id' => $user->getKey(),
        'provider' => BillingProviderEnum::Barte,
        'provider_customer_id' => 'buyer-uuid',
    ]);

    $data = new CheckoutData(
        planSlug: $plan->slug,
        priceId: $price->provider_price_id,
        isMetered: false,
        quantity: 1,
        trialDays: null,
        allowPromotionCodes: false,
        collectTaxIds: false,
        successUrl: 'https://app.test/success',
        cancelUrl: 'https://app.test/cancel',
    );

    $url = (new BarteAdapter(new BarteClient))->createCheckout($user, $data);

    expect($url)->toBe('https://pay.barte.test/checkout');

    Http::assertSent(function (Request $request) use ($price): bool {
        $metadata = $request->data()['metadata'] ?? [];

        return collect($metadata)->contains(
            fn (array $entry): bool => $entry['key'] === 'barte_price_id'
                && $entry['value'] === $price->provider_price_id
        );
    });
});
