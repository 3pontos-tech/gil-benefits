<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\Fakes\FakeBillingContract;
use TresPontosTech\Billing\Core\Actions\ResolveBenefitAccess;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Http\Middleware\RedirectUserIfNotSubscribed;
use TresPontosTech\PanelCompany\Http\Middleware\RedirectCompanyIfNotSubscribed;

// ─── test doubles ────────────────────────────────────────────────────────────

function makeCompanyMiddleware(): RedirectCompanyIfNotSubscribed
{
    $manager = Mockery::mock(BillingManager::class);
    $manager->shouldReceive('getDriver')->andReturn(new FakeBillingContract);

    return new RedirectCompanyIfNotSubscribed($manager);
}

function makeUserMiddleware(): RedirectUserIfNotSubscribed
{
    $manager = Mockery::mock(BillingManager::class);
    $manager->shouldReceive('getDriver')->andReturn(new FakeBillingContract);

    return new RedirectUserIfNotSubscribed(new ResolveBenefitAccess(resolve(PlanRepository::class)), $manager);
}

function legacyActiveSubscription(Company|User $owner, string $type): void
{
    $owner->subscriptions()->create([
        'type' => $type,
        'stripe_id' => 'sub_' . uniqid(),
        'stripe_status' => 'active',
        'quantity' => 1,
    ]);
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

        $this->stripePlan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::Company])->create();
        $this->bartePlan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::Company])->create();
        Price::factory()->for($this->stripePlan, 'plan')->create();
        Price::factory()->for($this->bartePlan, 'plan')->create();

        filament()->setCurrentPanel(FilamentPanel::Company->value);
        $this->actingAs($owner);
        filament()->setTenant($this->company);
    });

    it('lets a company through when it has an active Stripe subscription', function (): void {
        legacyActiveSubscription($this->company, $this->stripePlan->slug);

        $middleware = makeCompanyMiddleware();

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getContent())->toBe('ok');
    });

    it('no longer lets a company through on a plan of a retired gateway', function (): void {
        legacyActiveSubscription($this->company, $this->bartePlan->slug);

        $middleware = makeCompanyMiddleware();

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->isRedirect())->toBeTrue();
    });

    it('no longer lets a company through on a retired gateway price, even under an active plan slug', function (): void {
        $this->company->subscriptions()->create([
            'type' => $this->stripePlan->slug,
            'stripe_id' => 'sub_retired',
            'stripe_status' => 'active',
            'stripe_price' => $this->bartePlan->prices()->value('provider_price_id'),
            'quantity' => 1,
        ]);

        $response = makeCompanyMiddleware()->handle(fakeRequest(), passThrough());

        expect($response->isRedirect())->toBeTrue();
    });

    it('redirects a company that has no subscription on any provider', function (): void {
        $middleware = makeCompanyMiddleware();

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

        $plan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create();

        $this->standardPrice = Price::factory()->for($plan, 'plan')->create([
            'provider_price_id' => $plan->provider_product_id,
            'metadata' => [],
        ]);

        $this->flammaPrice = Price::factory()->for($plan, 'plan')->create([
            'provider_price_id' => $plan->provider_product_id . '-standalone-user',
            'audience' => PriceAudienceEnum::Standalone,
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

        $middleware = makeUserMiddleware();

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

        $middleware = makeUserMiddleware();

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

        $this->stripePlan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create();
        $this->bartePlan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create();
        Price::factory()->for($this->stripePlan, 'plan')->create();
        Price::factory()->for($this->bartePlan, 'plan')->create();

        filament()->setCurrentPanel(FilamentPanel::User->value);
        $this->actingAs($this->employee);
        filament()->setTenant($this->company);
    });

    it('lets a user through when both the company and the user are on a Stripe plan', function (): void {
        legacyActiveSubscription($this->company, 'company');
        legacyActiveSubscription($this->employee, $this->stripePlan->slug);

        $middleware = makeUserMiddleware();

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getContent())->toBe('ok');
    });

    it('no longer lets a user through on a personal plan of a retired gateway', function (): void {
        legacyActiveSubscription($this->company, 'company');
        legacyActiveSubscription($this->employee, $this->bartePlan->slug);

        $middleware = makeUserMiddleware();

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->isRedirect())->toBeTrue();
    });

    it('treats a company paying through a retired gateway as having no plan', function (): void {
        $this->company->subscriptions()->create([
            'type' => 'company',
            'stripe_id' => 'sub_retired',
            'stripe_status' => 'defaulter',
            'stripe_price' => $this->bartePlan->prices()->value('provider_price_id'),
            'quantity' => 1,
        ]);
        legacyActiveSubscription($this->employee, $this->stripePlan->slug);

        $response = makeUserMiddleware()->handle(fakeRequest(), passThrough());

        expect($response->headers->get('Location'))->toContain('company-plan-inactive');
    });

    it('redirects to the plan-inactive page when the company has no active subscription on any provider', function (): void {
        $middleware = makeUserMiddleware();

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->getStatusCode())->toBe(302)
            ->and($response->headers->get('Location'))->toContain('company-plan-inactive');
    });

    it('redirects a user to the plans page when the company is subscribed but the user has not picked a plan yet', function (): void {
        legacyActiveSubscription($this->company, 'company');

        $middleware = makeUserMiddleware();

        $response = $middleware->handle(fakeRequest(), passThrough());

        expect($response->isRedirect())->toBeTrue();
    });
});
