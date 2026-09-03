<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Laravel\Cashier\Cashier;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Stripe\Subscription\User\RedirectUserIfNotSubscribed;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Http::preventStrayRequests();
    Http::fake([
        '*/v2/buyers' => Http::response(['uuid' => 'fake-barte-buyer-uuid'], 201),
    ]);

    $this->employee = User::factory()->employee()->create([
        'stripe_id' => 'cus_user_' . uniqid(),
    ]);
    $this->company = Company::factory()->create([
        'stripe_id' => 'cus_company_' . uniqid(),
    ]);
    $this->company->employees()->attach($this->employee->getKey());

    filament()->setCurrentPanel(FilamentPanel::User->value);
    actingAs($this->employee);
    filament()->setTenant($this->company);

    $this->middleware = resolve(RedirectUserIfNotSubscribed::class);
    $this->request = Request::create('/app/test');
    $this->next = fn (Request $req): Response => new Response('ok');
});

afterEach(function (): void {
    Cashier::useCustomerModel(User::class);
});

it('allows access when company has an active contractual plan', function (): void {
    CompanyPlan::factory()->active()->for($this->company)->create();

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('redirects to the plan-inactive page when company has no stripe subscription', function (): void {
    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('company-plan-inactive');
});

it('redirects to the plan-inactive page when company subscription is past_due', function (): void {
    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_pastdue_' . uniqid(),
        'stripe_status' => 'past_due',
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('company-plan-inactive');
});

it('redirects to the plan-inactive page when company subscription is canceled', function (): void {
    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_canceled_' . uniqid(),
        'stripe_status' => 'canceled',
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('company-plan-inactive');
});

it('bypasses 403 check for flamma-company tenant', function (): void {
    $flammaCompany = Company::factory()->create([
        'slug' => 'flamma-company',
        'stripe_id' => 'cus_flamma',
    ]);
    $flammaCompany->employees()->attach($this->employee->getKey());
    filament()->setTenant($flammaCompany);

    $plan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create(['slug' => 'user-gold']);
    $flammaPrice = Price::factory()->for($plan, 'plan')->create([
        'provider_price_id' => 'price_flamma_gold',
        'audience' => PriceAudienceEnum::Standalone,
    ]);
    $this->employee->subscriptions()->create([
        'type' => 'user-gold',
        'stripe_id' => 'sub_flamma_user_' . uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $flammaPrice->provider_price_id,
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('redirects employee to subscription page when company is active but employee has no plan', function (): void {
    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_company_active_' . uniqid(),
        'stripe_status' => 'active',
    ]);
    $plan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create(['slug' => 'user-gold']);
    Price::factory()->for($plan, 'plan')->create();

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('available-subscriptions');
});

it('allows access when both company and employee have active subscriptions', function (): void {
    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_company_' . uniqid(),
        'stripe_status' => 'active',
    ]);
    $plan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create(['slug' => 'user-gold']);
    Price::factory()->for($plan, 'plan')->create();
    $this->employee->subscriptions()->create([
        'type' => $plan->slug,
        'stripe_id' => 'sub_employee_' . uniqid(),
        'stripe_status' => 'active',
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('allows access when employee subscription is trialing', function (): void {
    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_company_' . uniqid(),
        'stripe_status' => 'active',
    ]);
    $plan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create(['slug' => 'user-platinum']);
    Price::factory()->for($plan, 'plan')->create();
    $this->employee->subscriptions()->create([
        'type' => $plan->slug,
        'stripe_id' => 'sub_trial_' . uniqid(),
        'stripe_status' => 'trialing',
        'trial_ends_at' => now()->addDays(7),
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});
