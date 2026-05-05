<?php

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Company\Models\Company;

it('creates a billing customer for a company with a stripe id', function (): void {
    $company = Company::factory()->create(['stripe_id' => 'cus_company_test']);

    $this->artisan('billing:sync-customers')->assertSuccessful();

    expect(BillingCustomer::query()
        ->where('billable_type', $company->getMorphClass())
        ->where('billable_id', $company->getKey())
        ->where('provider', BillingProviderEnum::Stripe)
        ->where('provider_customer_id', 'cus_company_test')
        ->exists()
    )->toBeTrue();
});

it('creates a billing customer for a user with a stripe id', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_user_test']);

    $this->artisan('billing:sync-customers')->assertSuccessful();

    expect(BillingCustomer::query()
        ->where('billable_type', $user->getMorphClass())
        ->where('billable_id', $user->getKey())
        ->where('provider', BillingProviderEnum::Stripe)
        ->where('provider_customer_id', 'cus_user_test')
        ->exists()
    )->toBeTrue();
});

it('skips companies without a stripe id', function (): void {
    Company::factory()->create(['stripe_id' => null]);

    $this->artisan('billing:sync-customers')->assertSuccessful();

    expect(BillingCustomer::query()->count())->toBe(0);
});

it('skips users without a stripe id', function (): void {
    User::factory()->create(['stripe_id' => null]);

    $this->artisan('billing:sync-customers')->assertSuccessful();

    expect(BillingCustomer::query()->count())->toBe(0);
});

it('skips a company that already has a billing customer for stripe', function (): void {
    $company = Company::factory()->create(['stripe_id' => 'cus_already_synced']);

    BillingCustomer::factory()->create([
        'billable_type' => $company->getMorphClass(),
        'billable_id' => $company->getKey(),
        'provider' => BillingProviderEnum::Stripe,
        'provider_customer_id' => 'cus_already_synced',
    ]);

    $this->artisan('billing:sync-customers')->assertSuccessful();

    expect(BillingCustomer::query()->count())->toBe(1);
});

it('skips a user that already has a billing customer for stripe', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_already_synced']);

    BillingCustomer::factory()->create([
        'billable_type' => $user->getMorphClass(),
        'billable_id' => $user->getKey(),
        'provider' => BillingProviderEnum::Stripe,
        'provider_customer_id' => 'cus_already_synced',
    ]);

    $this->artisan('billing:sync-customers')->assertSuccessful();

    expect(BillingCustomer::query()->count())->toBe(1);
});

it('syncs multiple companies and users in one run', function (): void {
    Company::factory(3)->create(['stripe_id' => 'cus_company_1']);
    User::factory(2)->create(['stripe_id' => 'cus_user_1']);

    $this->artisan('billing:sync-customers')->assertSuccessful();

    expect(BillingCustomer::query()->count())->toBe(5);
});

it('does not duplicate billing customers on consecutive runs', function (): void {
    Company::factory()->create(['stripe_id' => 'cus_idempotent']);
    User::factory()->create(['stripe_id' => 'cus_idempotent_user']);

    $this->artisan('billing:sync-customers')->assertSuccessful();
    $this->artisan('billing:sync-customers')->assertSuccessful();

    expect(BillingCustomer::query()->count())->toBe(2);
});
