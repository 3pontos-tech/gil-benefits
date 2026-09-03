<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

it('stores the monthly appointment quota given on the command line', function (): void {
    $this->artisan('virtu:plan:create', [
        'slug' => 'flamma-platinum',
        'name' => 'Flamma Platinum',
        'amount' => 27990,
        '--type' => 'user',
        '--audience' => 'subsidized',
        '--monthly-appointments' => 2,
    ])->assertSuccessful();

    $price = Price::query()->where('provider_price_id', 'flamma-platinum-monthly-subsidized')->sole();

    expect($price->monthly_appointments)->toBe(2)
        ->and($price->unit_amount_decimal)->toBe(27990)
        ->and($price->audience)->toBe(PriceAudienceEnum::Subsidized);
});

it('falls back to a single appointment when the quota is omitted', function (): void {
    $this->artisan('virtu:plan:create', [
        'slug' => 'flamma-gold',
        'name' => 'Flamma Gold',
        'amount' => 17990,
        '--type' => 'user',
    ])->assertSuccessful();

    expect(Price::query()->where('provider_price_id', 'flamma-gold-monthly-subsidized')->sole()->monthly_appointments)
        ->toBe(1);
});

it('gives each audience its own price under the same plan', function (): void {
    $arguments = [
        'slug' => 'flamma-platinum',
        'name' => 'Flamma Platinum',
        '--type' => 'user',
    ];

    $this->artisan('virtu:plan:create', [...$arguments, 'amount' => 27990, '--audience' => 'subsidized', '--monthly-appointments' => 2])
        ->assertSuccessful();
    $this->artisan('virtu:plan:create', [...$arguments, 'amount' => 30000, '--audience' => 'standalone', '--monthly-appointments' => 2])
        ->assertSuccessful();

    $plan = Plan::query()
        ->where('provider', BillingProviderEnum::Virtu)
        ->where('slug', 'flamma-platinum')
        ->sole();

    expect($plan->type)->toBe(BillableTypeEnum::User)
        ->and($plan->active)->toBeTruthy()
        ->and($plan->prices()->count())->toBe(2)
        ->and($plan->prices()->where('audience', PriceAudienceEnum::Standalone->value)->sole())
        ->unit_amount_decimal->toBe(30000)
        ->monthly_appointments->toBe(2);
});

it('rejects an unknown audience', function (): void {
    $this->artisan('virtu:plan:create', [
        'slug' => 'flamma-gold',
        'name' => 'Flamma Gold',
        'amount' => 17990,
        '--audience' => 'gratis',
    ])->assertFailed();

    expect(Plan::query()->where('slug', 'flamma-gold')->exists())->toBeFalse();
});
