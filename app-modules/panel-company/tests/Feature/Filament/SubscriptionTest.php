<?php

use App\Filament\FilamentPanel;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fakes\FakeBillingContract;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Pages\TenantSubscriptionPage;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = actingAsAdmin(FilamentPanel::Company);

    $this->company = Company::factory()
        ->recycle($user)
        ->create();

    $this->company->employees()->attach($user);
    filament()->setTenant($this->company);

    $fake = new FakeBillingContract(isSubscribed: false);

    $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock) use ($fake) {
        $mock->makePartial();
        $mock->shouldReceive('getDriver')->andReturn($fake);
    }));
});

it('should redirect to available-subscriptions when company has no active subscription', function (): void {

    get(route('filament.company.pages.dashboard', ['tenant' => $this->company->slug]))
        ->assertStatus(Response::HTTP_FOUND)
        ->assertRedirect(route('filament.company.pages.available-subscriptions', ['tenant' => $this->company->slug]));
})->skipOnCI();

it('should access dashboard when company has an active contractual plan', function (): void {
    CompanyPlan::factory()->active()->create([
        'company_id' => $this->company->id,
    ]);

    get(route('filament.company.pages.dashboard', ['tenant' => $this->company->slug]))
        ->assertOk();
})->skipOnCI();

it('should access dashboard when company has an active billing subscription', function (): void {
    $plan = Plan::factory()->barte()->active()->create([
        'type' => BillableTypeEnum::Company,
        'provider_product_id' => 'prod_test_company',
        'slug' => 'plano-empresa-teste',
    ]);

    Price::factory()->create([
        'billing_plan_id' => $plan->id,
        'active' => true,
        'provider_price_id' => 'price_test_company',
    ]);

    $fake = new FakeBillingContract(isSubscribed: true);

    $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock) use ($fake) {
        $mock->makePartial();
        $mock->shouldReceive('getDriver')->andReturn($fake);
    }));

    get(route('filament.company.pages.dashboard', ['tenant' => $this->company->slug]))
        ->assertOk();
})->skipOnCI();

it('should create a subscription when checkout is called', function (): void {
    $plan = Plan::factory()->barte()->active()->create([
        'type' => BillableTypeEnum::Company,
        'provider_product_id' => 'prod_test_company',
        'slug' => 'plano-empresa-teste',
    ]);

    Price::factory()->create([
        'billing_plan_id' => $plan->id,
        'active' => true,
        'provider_price_id' => 'price_test_company',
    ]);

    $fake = new FakeBillingContract(
        isSubscribed: false,
        createCheckoutUsing: function (Company $billable, CheckoutData $data): string {
            $billable->subscriptions()->create([
                'type' => 'company',
                'stripe_id' => 'sub_fake_' . $billable->getKey(),
                'stripe_status' => 'active',
                'stripe_price' => $data->priceId,
                'quantity' => $data->quantity,
            ]);

            return 'https://checkout.test';
        },
    );

    $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock) use ($fake) {
        $mock->makePartial();
        $mock->shouldReceive('getDriver')->andReturn($fake);
    }));

    livewire(TenantSubscriptionPage::class)
        ->call('checkout')
        ->assertRedirect('https://checkout.test');

    assertDatabaseHas(Subscription::class, [
        'subscriptionable_type' => $this->company->getMorphClass(),
        'subscriptionable_id' => $this->company->getKey(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test_company',
    ]);
})->skipOnCI();
