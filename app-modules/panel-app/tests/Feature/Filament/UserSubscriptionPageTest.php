<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use Tests\Fakes\FakeBillingContract;
use Tests\Fakes\FakeDiscountableBillingContract;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;
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

describe('desconto do convite da Clau', function (): void {
    beforeEach(function (): void {
        DiscountGrant::factory()
            ->for(Discount::factory()->active())
            ->claimedBy($this->employee)
            ->create();
    });

    it('mostra o preço com desconto no tenant flamma', function (): void {
        $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock): void {
            $mock->makePartial();
            $mock->shouldReceive('getDriver')->andReturn(new FakeDiscountableBillingContract);
        }));

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->flamma);

        livewire(UserSubscriptionPage::class)
            ->assertSee('350')
            ->assertSee('245')
            ->assertSee('30% de desconto');
    });

    it('cobra o checkout com o percentual do desconto escolhido', function (): void {
        $captured = null;

        $fake = new FakeDiscountableBillingContract(createCheckoutUsing: function ($billable, CheckoutData $data) use (&$captured): string {
            $captured = $data;

            return 'https://checkout.test';
        });

        $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock) use ($fake): void {
            $mock->makePartial();
            $mock->shouldReceive('getDriver')->andReturn($fake);
        }));

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->flamma);

        $offerKey = 'grant:' . DiscountGrant::query()->value('id');

        livewire(UserSubscriptionPage::class)
            ->assertSet('selectedOffers.' . $this->plan->slug, $offerKey)
            ->call('checkout', $this->plan->slug, $offerKey);

        expect($captured?->discount?->percentOff)->toBe(30);
        expect(DiscountRedemption::query()->where('plan_slug', $this->plan->slug)->where('status', 'reserved')->count())->toBe(1);
    });

    it('não oferece desconto quando o driver não suporta desconto no checkout', function (): void {
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
        filament()->setTenant($this->flamma);

        livewire(UserSubscriptionPage::class)
            ->assertDontSee('30% de desconto')
            ->call('checkout', $this->plan->slug);

        expect($captured?->discount)->toBeNull();
    });

    it('não aplica desconto no tenant subsidiado', function (): void {
        $captured = null;

        $fake = new FakeDiscountableBillingContract(createCheckoutUsing: function ($billable, CheckoutData $data) use (&$captured): string {
            $captured = $data;

            return 'https://checkout.test';
        });

        $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock) use ($fake): void {
            $mock->makePartial();
            $mock->shouldReceive('getDriver')->andReturn($fake);
        }));

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->company);

        livewire(UserSubscriptionPage::class)
            ->assertDontSee('30% de desconto')
            ->call('checkout', $this->plan->slug);

        expect($captured?->discount)->toBeNull();
    });

    it('não aplica desconto com o grant vencido', function (): void {
        DiscountGrant::query()->update(['expires_at' => now()->subDay()]);

        $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock): void {
            $mock->makePartial();
            $mock->shouldReceive('getDriver')->andReturn(new FakeDiscountableBillingContract);
        }));

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->flamma);

        livewire(UserSubscriptionPage::class)
            ->assertSee('350')
            ->assertDontSee('30% de desconto');
    });

    it('não aplica desconto quando o grant está travado em outro plano', function (): void {
        DiscountGrant::query()->update(['plan_slug' => 'outro-plano']);

        $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock): void {
            $mock->makePartial();
            $mock->shouldReceive('getDriver')->andReturn(new FakeDiscountableBillingContract);
        }));

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->flamma);

        livewire(UserSubscriptionPage::class)
            ->assertSee('350')
            ->assertDontSee('30% de desconto');
    });
});

describe('seletor de cupons', function (): void {
    beforeEach(function (): void {
        $this->gateway = new FakeDiscountableBillingContract;
        $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock): void {
            $mock->makePartial();
            $mock->shouldReceive('getDriver')->andReturn($this->gateway);
        }));

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->flamma);
    });

    it('pré-seleciona o cupom mais vantajoso e lista a quantidade disponível', function (): void {
        $weakGrant = DiscountGrant::factory()
            ->for(Discount::factory()->active()->state(['name' => 'Cupom Fraco', 'percent_off' => 10]))
            ->claimedBy($this->employee)
            ->create();

        $strongGrant = DiscountGrant::factory()
            ->for(Discount::factory()->active()->state(['name' => 'Cupom Forte', 'percent_off' => 50]))
            ->claimedBy($this->employee)
            ->create();

        livewire(UserSubscriptionPage::class)
            ->assertSee('2 cupons disponíveis')
            ->assertSee('Sem cupom')
            ->assertSet('selectedOffers.' . $this->plan->slug, 'grant:' . $strongGrant->getKey())
            ->assertSee('Cupom Fraco')
            ->assertSee('10% de desconto em todas as mensalidades');
    });

    it('não oferece cupom com duração limitada enquanto o gateway não suporta', function (): void {
        DiscountGrant::factory()
            ->for(Discount::factory()->active()->repeating(3)->state(['name' => 'Cupom Limitado']))
            ->claimedBy($this->employee)
            ->create();

        livewire(UserSubscriptionPage::class)
            ->assertDontSee('Cupom Limitado')
            ->assertDontSee('cupom disponível');
    });

    it('faz checkout sem cupom quando a pessoa escolhe sem cupom', function (): void {
        $captured = null;
        $this->gateway = new FakeDiscountableBillingContract(createCheckoutUsing: function ($billable, CheckoutData $data) use (&$captured): string {
            $captured = $data;

            return 'https://checkout.test';
        });

        $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock): void {
            $mock->makePartial();
            $mock->shouldReceive('getDriver')->andReturn($this->gateway);
        }));

        DiscountGrant::factory()->for(Discount::factory()->active())->claimedBy($this->employee)->create();

        livewire(UserSubscriptionPage::class)
            ->call('checkout', $this->plan->slug, '');

        expect($captured?->discount)->toBeNull();
        expect(DiscountRedemption::query()->count())->toBe(0);
    });

    it('recusa uma key adulterada sem gerar link nem reserva', function (): void {
        $called = false;
        $this->gateway = new FakeDiscountableBillingContract(createCheckoutUsing: function () use (&$called): string {
            $called = true;

            return 'https://checkout.test';
        });

        $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock): void {
            $mock->makePartial();
            $mock->shouldReceive('getDriver')->andReturn($this->gateway);
        }));

        DiscountGrant::factory()->for(Discount::factory()->active())->claimedBy($this->employee)->create();

        livewire(UserSubscriptionPage::class)
            ->call('checkout', $this->plan->slug, 'grant:chave-inventada')
            ->assertNotified('Este cupom não está mais disponível');

        expect($called)->toBeFalse();
        expect(DiscountRedemption::query()->count())->toBe(0);
    });

    it('recusa o cupom que deixou de valer entre a renderização e o checkout', function (): void {
        $grant = DiscountGrant::factory()
            ->for(Discount::factory()->active())
            ->claimedBy($this->employee)
            ->create();

        $component = livewire(UserSubscriptionPage::class);
        $offerKey = 'grant:' . $grant->getKey();

        $grant->update(['revoked_at' => now()]);

        $component
            ->call('checkout', $this->plan->slug, $offerKey)
            ->assertNotified('Este cupom não está mais disponível');

        expect(DiscountRedemption::query()->count())->toBe(0);
    });

    it('recusa o cupom quando o limite total já foi atingido', function (): void {
        $discount = Discount::factory()->active()->state(['max_redemptions' => 1])->create();
        $grant = DiscountGrant::factory()->for($discount)->claimedBy($this->employee)->create();

        DiscountRedemption::factory()->for($discount)->active()->create();

        livewire(UserSubscriptionPage::class)
            ->call('checkout', $this->plan->slug, 'grant:' . $grant->getKey())
            ->assertNotified('Este cupom não está mais disponível');

        expect(DiscountRedemption::query()->where('discount_id', $discount->getKey())->count())->toBe(1);
    });

    it('aplica um código digitado e pré-seleciona o novo cupom', function (): void {
        Discount::factory()->code('CLAU30')->active()->state(['name' => 'Cupom por código', 'percent_off' => 25])->create();

        livewire(UserSubscriptionPage::class)
            ->set('discountCode', 'clau 30')
            ->call('applyCode')
            ->assertHasNoErrors()
            ->assertSet('discountCode', '')
            ->assertSee('Cupom por código')
            ->assertSet('selectedOffers.' . $this->plan->slug, 'grant:' . DiscountGrant::query()->where('source', 'code')->value('id'));
    });

    it('mostra erro para um código inválido', function (): void {
        livewire(UserSubscriptionPage::class)
            ->set('discountCode', 'NAOEXISTE')
            ->call('applyCode')
            ->assertHasErrors(['discountCode']);
    });

    it('não duplica o cupom do mesmo código', function (): void {
        Discount::factory()->code('DUPLICADO')->active()->create();

        $component = livewire(UserSubscriptionPage::class)
            ->set('discountCode', 'DUPLICADO')
            ->call('applyCode')
            ->assertHasNoErrors();

        $component
            ->set('discountCode', 'DUPLICADO')
            ->call('applyCode')
            ->assertHasErrors(['discountCode']);

        expect(DiscountGrant::query()->where('user_id', $this->employee->getKey())->where('source', 'code')->count())->toBe(1);
    });
});
