<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use Tests\Fakes\FakeBillingContract;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\UserSubscriptionPage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Cache::flush();

    $owner = User::factory()->companyOwner()->create();
    $this->employee = User::factory()->employee()->create();

    $this->flamma = Company::factory()->recycle($owner)->create(['slug' => 'flamma-company']);
    $this->flamma->employees()->attach($this->employee->getKey());

    $this->company = Company::factory()->recycle(User::factory()->companyOwner()->create())->create();
    $this->company->employees()->attach($this->employee->getKey());

    // O provider é o que estiver vendendo hoje: o teste é sobre qual preço a
    // tela mostra dentro e fora do tenant flamma, não sobre o gateway.
    $this->plan = Plan::factory()->active()
        ->forProvider(BillingProviderEnum::checkoutCases()[0])
        ->state(['type' => BillableTypeEnum::User, 'name' => 'Plano Teste'])
        ->create();

    // Subsidized price — for employees whose company covers part of it
    Price::factory()->for($this->plan, 'plan')->create([
        'provider_price_id' => 'price-standard',
        'unit_amount_decimal' => 19900,
        'audience' => PriceAudienceEnum::Subsidized,
    ]);

    // Standalone price — full value, for users with no employer behind them
    Price::factory()->for($this->plan, 'plan')->create([
        'provider_price_id' => 'price-standalone-user',
        'unit_amount_decimal' => 35000,
        'audience' => PriceAudienceEnum::Standalone,
    ]);
});

it('displays the flamma price when in the flamma-company tenant', function (): void {
    filament()->setCurrentPanel(FilamentPanel::User->value);
    $this->actingAs($this->employee);
    filament()->setTenant($this->flamma);

    livewire(UserSubscriptionPage::class)
        ->assertSee('350')
        ->assertDontSee('199');
});

it('displays the standard price when in a non-flamma tenant', function (): void {
    filament()->setCurrentPanel(FilamentPanel::User->value);
    $this->actingAs($this->employee);
    filament()->setTenant($this->company);

    livewire(UserSubscriptionPage::class)
        ->assertSee('199')
        ->assertDontSee('350');
});

it('checks out with the price matching the tenant audience', function (string $tenant, string $expectedPriceId): void {
    $captured = null;

    $fake = new FakeBillingContract(createCheckoutUsing: function ($billable, CheckoutData $data) use (&$captured): string {
        $captured = $data;

        return 'https://checkout.test';
    });

    $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock) use ($fake): void {
        $mock->makePartial();
        $mock->shouldReceive('getDriver')->andReturn($fake);
    }));

    filament()->setCurrentPanel(FilamentPanel::User->value);
    $this->actingAs($this->employee);
    filament()->setTenant($this->{$tenant});

    livewire(UserSubscriptionPage::class)->call('checkout', $this->plan->slug);

    expect($captured?->priceId)->toBe($expectedPriceId);
})->with([
    'employer subsidizes' => ['company', 'price-standard'],
    'no employer behind the user' => ['flamma', 'price-standalone-user'],
]);

it('hides a plan that has no price for the tenant audience', function (): void {
    // Plano só com preço subsidiado: dentro do tenant default não há valor
    // cheio a cobrar, então ele não pode aparecer.
    $subsidizedOnly = Plan::factory()->active()
        ->forProvider(BillingProviderEnum::checkoutCases()[0])
        ->state(['type' => BillableTypeEnum::User, 'name' => 'Somente Subsidiado'])
        ->create();

    Price::factory()->for($subsidizedOnly, 'plan')->create([
        'provider_price_id' => 'price-subsidized-only',
        'unit_amount_decimal' => 12300,
        'audience' => PriceAudienceEnum::Subsidized,
    ]);

    filament()->setCurrentPanel(FilamentPanel::User->value);
    $this->actingAs($this->employee);
    filament()->setTenant($this->flamma);

    livewire(UserSubscriptionPage::class)
        ->assertSee('Plano Teste')
        ->assertDontSee('Somente Subsidiado');
});
