<?php

// These tests ensure users/companies with active subscriptions from legacy providers
// (e.g. Stripe) are never blocked after migrating to a new gateway (e.g. Barte).
// Both gateways must be accepted as valid during the transition period.

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\Fakes\FakeBillingContract;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Billing\Stripe\Subscription\Company\RedirectCompanyIfNotSubscribed;
use TresPontosTech\Billing\Stripe\Subscription\User\RedirectUserIfNotSubscribed;
use TresPontosTech\Company\Models\Company;

// ─── test doubles ────────────────────────────────────────────────────────────

/**
 * Builds the company middleware with fake Stripe and Barte drivers.
 * Pass isSubscribed: true to simulate an active subscription on that provider.
 */
function makeCompanyMiddleware(
    FakeBillingContract $stripe,
    FakeBillingContract $barte,
): RedirectCompanyIfNotSubscribed {
    $manager = Mockery::mock(BillingManager::class);
    $manager->shouldReceive('getDriver')->with(BillingProviderEnum::Stripe)->andReturn($stripe);
    $manager->shouldReceive('getDriver')->with(BillingProviderEnum::Barte)->andReturn($barte);

    return new RedirectCompanyIfNotSubscribed($manager);
}

/**
 * Builds the user middleware with fake Stripe and Barte drivers.
 * hasActiveSubscription controls the company-level check;
 * isSubscribed controls the employee-level plan check.
 */
function makeUserMiddleware(
    FakeBillingContract $stripe,
    FakeBillingContract $barte,
): RedirectUserIfNotSubscribed {
    $manager = Mockery::mock(BillingManager::class);
    $manager->shouldReceive('getDriver')->with(BillingProviderEnum::Stripe)->andReturn($stripe);
    $manager->shouldReceive('getDriver')->with(BillingProviderEnum::Barte)->andReturn($barte);

    return new RedirectUserIfNotSubscribed(resolve(PlanRepository::class), $manager);
}

function fakeRequest(): Request
{
    return Request::create('/dashboard');
}

function passThrough(): Closure
{
    return fn (Request $r): Response => new Response('ok');
}

// ─── company access ──────────────────────────────────────────────────────────

describe('company access during gateway migration', function (): void {
    beforeEach(function (): void {
        $owner = User::factory()->companyOwner()->create();
        $this->company = Company::factory()->recycle($owner)->create();

        // Seed one active plan per provider so the middleware has slugs to check against
        Price::factory()->for(Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::Company])->create(), 'plan')->create();
        Price::factory()->for(Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::Company])->create(), 'plan')->create();

        filament()->setCurrentPanel(FilamentPanel::Company->value);
        $this->actingAs($owner);
        filament()->setTenant($this->company);
    });

    it('lets a company through when it has an active Stripe subscription (legacy)', function (): void {
        $middleware = makeCompanyMiddleware(
            stripe: new FakeBillingContract(isSubscribed: true),
            barte: new FakeBillingContract(isSubscribed: false),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getContent())->toBe('ok');
    });

    it('lets a company through when it has an active Barte subscription (current gateway)', function (): void {
        $middleware = makeCompanyMiddleware(
            stripe: new FakeBillingContract(isSubscribed: false),
            barte: new FakeBillingContract(isSubscribed: true),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getContent())->toBe('ok');
    });

    it('redirects a company that has no subscription on any provider', function (): void {
        $middleware = makeCompanyMiddleware(
            stripe: new FakeBillingContract(isSubscribed: false),
            barte: new FakeBillingContract(isSubscribed: false),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->isRedirect())->toBeTrue();
    });
});

// ─── flamma-company subscription isolation ────────────────────────────────────

describe('flamma-company subscription isolation', function (): void {
    beforeEach(function (): void {
        $owner = User::factory()->companyOwner()->create();
        $this->employee = User::factory()->employee()->create();
        $this->flamma = Company::factory()->recycle($owner)->create(['slug' => 'flamma-company']);
        $this->flamma->employees()->attach($this->employee->getKey());

        $plan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create();

        $this->standardPrice = Price::factory()->for($plan, 'plan')->create([
            'provider_price_id' => $plan->provider_product_id,
            'metadata' => [],
        ]);

        $this->flammaPrice = Price::factory()->for($plan, 'plan')->create([
            'provider_price_id' => $plan->provider_product_id . '-standalone-user',
            'metadata' => ['tenant' => 'flamma-company'],
        ]);

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->flamma);
    });

    it('lets a user through when they have an active flamma-specific subscription', function (): void {
        $this->employee->subscriptions()->create([
            'type' => 'test-plan',
            'stripe_id' => 'sub_flamma_test',
            'stripe_status' => 'active',
            'stripe_price' => $this->flammaPrice->provider_price_id,
            'quantity' => 1,
        ]);

        $middleware = makeUserMiddleware(
            stripe: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
            barte: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getContent())->toBe('ok');
    });

    it('redirects a user whose subscription uses the standard price when in flamma-company', function (): void {
        $this->employee->subscriptions()->create([
            'type' => 'test-plan',
            'stripe_id' => 'sub_standard_test',
            'stripe_status' => 'active',
            'stripe_price' => $this->standardPrice->provider_price_id,
            'quantity' => 1,
        ]);

        $middleware = makeUserMiddleware(
            stripe: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
            barte: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->isRedirect())->toBeTrue();
    });
});

// ─── user (employee) access ───────────────────────────────────────────────────

describe('user access during gateway migration', function (): void {
    beforeEach(function (): void {
        $owner = User::factory()->companyOwner()->create();
        $this->employee = User::factory()->employee()->create();
        $this->company = Company::factory()->recycle($owner)->create();
        $this->company->employees()->attach($this->employee->getKey());

        // Seed one active user plan per provider so getPlansFor() returns slugs to verify
        Price::factory()->for(Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create(), 'plan')->create();
        Price::factory()->for(Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create(), 'plan')->create();

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->company);
    });

    it('lets a user through when both the company and the user are on a legacy Stripe plan', function (): void {
        $middleware = makeUserMiddleware(
            stripe: new FakeBillingContract(isSubscribed: true, hasActiveSubscription: true),
            barte: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getContent())->toBe('ok');
    });

    it('lets a user through when both the company and the user are on a Barte plan', function (): void {
        $middleware = makeUserMiddleware(
            stripe: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
            barte: new FakeBillingContract(isSubscribed: true, hasActiveSubscription: true),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getContent())->toBe('ok');
    });

    it('redirects to the plan-inactive page when the company has no active subscription on any provider', function (): void {
        $middleware = makeUserMiddleware(
            stripe: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
            barte: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getStatusCode())->toBe(302)
            ->and($response->headers->get('Location'))->toContain('company-plan-inactive');
    });

    it('redirects a user to the plans page when the company is subscribed but the user has not picked a plan yet', function (): void {
        $middleware = makeUserMiddleware(
            // Company has a Stripe subscription, but employee has no individual plan on either provider
            stripe: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: true),
            barte: new FakeBillingContract(isSubscribed: false, hasActiveSubscription: false),
        );

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->isRedirect())->toBeTrue();
    });
});
