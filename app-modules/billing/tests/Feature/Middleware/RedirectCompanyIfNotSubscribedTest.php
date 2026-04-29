<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Stripe\Subscription\Company\RedirectCompanyIfNotSubscribed;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->companyOwner()->create();
    $this->company = Company::factory()->recycle($this->user)->create([
        'stripe_id' => 'cus_test_' . uniqid(),
    ]);
    $this->company->employees()->attach($this->user->getKey());

    filament()->setCurrentPanel(FilamentPanel::Company->value);
    actingAs($this->user);
    filament()->setTenant($this->company);

    $this->middleware = resolve(RedirectCompanyIfNotSubscribed::class);
    $this->request = Request::create('/company/test');
    $this->next = fn (Request $req): Response => new Response('ok');
});

it('bypasses check for flamma-company tenant', function (): void {
    $flammaCompany = Company::factory()->recycle($this->user)->create([
        'slug' => 'flamma-company',
        'stripe_id' => 'cus_flamma',
    ]);
    $flammaCompany->employees()->attach($this->user->getKey());
    filament()->setTenant($flammaCompany);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('allows access when company has an active contractual plan', function (): void {
    CompanyPlan::factory()->active()->for($this->company)->create();

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('allows access when company has an active stripe subscription', function (): void {
    $plan = Plan::factory()->active()->stripe()->create(['slug' => 'company-stripe-plan']);
    Price::factory()->for($plan, 'plan')->create();
    $this->company->subscriptions()->create([
        'type' => $plan->slug,
        'stripe_id' => 'sub_active_' . uniqid(),
        'stripe_status' => 'active',
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('allows access when company subscription status is trialing', function (): void {
    $plan = Plan::factory()->active()->stripe()->create(['slug' => 'company-trialing-plan']);
    Price::factory()->for($plan, 'plan')->create();
    $this->company->subscriptions()->create([
        'type' => $plan->slug,
        'stripe_id' => 'sub_trial_' . uniqid(),
        'stripe_status' => 'trialing',
        'trial_ends_at' => now()->addDays(14),
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('redirects when company subscription status is past_due', function (): void {
    $plan = Plan::factory()->active()->stripe()->create(['slug' => 'company-pastdue-plan']);
    Price::factory()->for($plan, 'plan')->create();
    $this->company->subscriptions()->create([
        'type' => $plan->slug,
        'stripe_id' => 'sub_pastdue_' . uniqid(),
        'stripe_status' => 'past_due',
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('available-subscriptions');
});

it('redirects when company subscription status is canceled and has ended', function (): void {
    $plan = Plan::factory()->active()->stripe()->create(['slug' => 'company-canceled-plan']);
    Price::factory()->for($plan, 'plan')->create();
    $this->company->subscriptions()->create([
        'type' => $plan->slug,
        'stripe_id' => 'sub_canceled_' . uniqid(),
        'stripe_status' => 'canceled',
        'ends_at' => now()->subDay(), // encerrada no passado
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('available-subscriptions');
});

it('redirects when company has no subscription at all', function (): void {
    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('available-subscriptions');
});
