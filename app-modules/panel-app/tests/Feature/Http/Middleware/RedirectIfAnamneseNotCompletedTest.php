<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Stripe\Subscription\User\RedirectUserIfNotSubscribed;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->employee = User::factory()->employee()->create();
    $this->company = Company::factory()->create();
    $this->company->employees()->attach($this->employee->getKey());

    $plan = Plan::factory()->createOne([
        'provider' => BillingProviderEnum::Stripe->value,
        'type' => BillableTypeEnum::User->value,
        'provider_product_id' => 'prod_test',
        'has_generic_trial' => false,
        'allow_promotion_codes' => false,
        'collect_tax_ids' => false,
        'active' => true,
        'slug' => 'plano-teste',
        'statement_descriptor' => 'PLANO TESTE',
    ]);

    CompanyPlan::query()->create([
        'company_id' => $this->company->id,
        'plan_id' => $plan->id,
        'status' => CompanyPlanStatusEnum::Active->value,
        'monthly_appointments_per_employee' => 1,
        'starts_at' => now()->subDay(),
        'seats' => 10,
    ]);

    filament()->setCurrentPanel(FilamentPanel::User->value);
    actingAs($this->employee);
    filament()->setTenant($this->company);
});

it('redirects a user with an active plan and no anamnese to the anamnese wizard', function (): void {
    $this->get(route('filament.app.pages.user-dashboard', ['tenant' => $this->company->slug]))
        ->assertRedirect(route('filament.app.pages.anamnese', ['tenant' => $this->company->slug]));
});

it('allows access when the anamnese is already filled', function (): void {
    UserAnamnese::factory()->create(['user_id' => $this->employee->id]);

    $this->get(route('filament.app.pages.user-dashboard', ['tenant' => $this->company->slug]))
        ->assertOk();
});

it('allows access to the anamnese route itself to avoid a redirect loop', function (): void {
    $this->get(route('filament.app.pages.anamnese', ['tenant' => $this->company->slug]))
        ->assertOk();
});

it('allows access when there is no active subscription or plan', function (): void {
    CompanyPlan::query()->where('company_id', $this->company->id)->delete();

    $this->withoutMiddleware(RedirectUserIfNotSubscribed::class)
        ->get(route('filament.app.pages.user-dashboard', ['tenant' => $this->company->slug]))
        ->assertOk();
});
