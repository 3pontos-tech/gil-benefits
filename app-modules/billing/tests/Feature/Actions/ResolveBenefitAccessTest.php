<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeBillingContract;
use TresPontosTech\Billing\Core\Actions\ResolveBenefitAccess;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Enums\BenefitAccessEnum;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectUserIfNotSubscribed;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Vouchers\Database\Factories\VoucherRedemptionFactory;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Http::preventStrayRequests();
    Http::fake(['*/v2/buyers' => Http::response(['uuid' => 'fake-barte-buyer-uuid'], 201)]);

    $this->employee = User::factory()->employee()->create(['stripe_id' => 'cus_user_' . uniqid()]);
    $this->company = Company::factory()->create(['stripe_id' => 'cus_company_' . uniqid()]);
    $this->company->employees()->attach($this->employee->getKey());
});

function benefitDefaultCompany(User $employee): Company
{
    $company = Company::query()->where('slug', Company::DEFAULT_SLUG)->first()
        ?? Company::factory()->create(['slug' => Company::DEFAULT_SLUG, 'stripe_id' => 'cus_default_' . uniqid()]);
    $company->employees()->syncWithoutDetaching([$employee->getKey()]);

    return $company;
}

function companySubscription(Company $company, string $status): void
{
    $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_company_' . uniqid(),
        'stripe_status' => $status,
    ]);
}

function employeePlan(): Plan
{
    $plan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create(['slug' => 'user-' . uniqid()]);
    Price::factory()->for($plan, 'plan')->create(['provider_price_id' => 'price_' . uniqid()]);

    return $plan;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function employeePlanSubscription(User $employee, string $status, ?PriceAudienceEnum $audience = null, array $attributes = []): void
{
    $plan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create(['slug' => 'user-' . uniqid()]);
    $price = Price::factory()->for($plan, 'plan')->create(array_filter([
        'provider_price_id' => 'price_' . uniqid(),
        'audience' => $audience,
    ]));

    $employee->subscriptions()->create([
        'type' => $plan->slug,
        'stripe_id' => 'sub_employee_' . uniqid(),
        'stripe_status' => $status,
        'stripe_price' => $price->provider_price_id,
        ...$attributes,
    ]);
}

function benefitVoucherCredit(User $employee, Company $company, array $attributes): void
{
    UserCredit::factory()->create([
        'holder_id' => $employee->getKey(),
        'owner_id' => $employee->getKey(),
        'company_id' => $company->getKey(),
        'voucher_redemption_id' => VoucherRedemptionFactory::new()->create()->getKey(),
        ...$attributes,
    ]);
}

function benefitAccess(User $employee, Company $company): BenefitAccessEnum
{
    return resolve(ResolveBenefitAccess::class)->for($employee->fresh(), $company->fresh());
}

describe('employer company', function (): void {
    it('grants by the contractual plan, even without a personal subscription', function (): void {
        CompanyPlan::factory()->active()->for($this->company)->create();

        expect(benefitAccess($this->employee, $this->company))->toBe(BenefitAccessEnum::ContractualPlan);
    });

    it('grants by a credits-only contract, as the panel does', function (): void {
        CompanyPlan::factory()->creditsOnly()->for($this->company)->create();

        expect(benefitAccess($this->employee, $this->company))->toBe(BenefitAccessEnum::ContractualPlan);
    });

    it('treats a contract that is not in force as no contract', function (string $state): void {
        CompanyPlan::factory()->{$state}()->for($this->company)->create();

        expect(benefitAccess($this->employee, $this->company))->toBe(BenefitAccessEnum::CompanyPlanInactive);
    })->with(['expired', 'inactive', 'notStartedYet']);

    it('reports the company plan as inactive without a contract or an active subscription', function (?string $status): void {
        if ($status !== null) {
            companySubscription($this->company, $status);
        }

        employeePlanSubscription($this->employee, 'active');

        expect(benefitAccess($this->employee, $this->company))->toBe(BenefitAccessEnum::CompanyPlanInactive);
    })->with([
        'no subscription' => [null],
        'past due' => ['past_due'],
        'canceled' => ['canceled'],
        'unpaid' => ['unpaid'],
    ]);

    it('asks for a personal plan when the company pays but the employee has none', function (string $companyStatus): void {
        companySubscription($this->company, $companyStatus);
        employeePlan();

        expect(benefitAccess($this->employee, $this->company))->toBe(BenefitAccessEnum::PersonalPlanRequired);
    })->with(['active', 'incomplete']);

    it('grants by the personal plan when the company pays', function (string $employeeStatus): void {
        companySubscription($this->company, 'active');
        employeePlanSubscription($this->employee, $employeeStatus);

        expect(benefitAccess($this->employee, $this->company))->toBe(BenefitAccessEnum::PersonalPlan);
    })->with(['active', 'trialing']);

    it('does not count a personal subscription that lost access', function (string $employeeStatus, array $attributes): void {
        companySubscription($this->company, 'active');
        employeePlanSubscription($this->employee, $employeeStatus, attributes: $attributes);

        expect(benefitAccess($this->employee, $this->company))->toBe(BenefitAccessEnum::PersonalPlanRequired);
    })->with([
        'canceled and ended' => ['canceled', ['ends_at' => now()->subDay()]],
        'past due' => ['past_due', []],
        'unpaid' => ['unpaid', []],
        'incomplete expired' => ['incomplete_expired', []],
        'defaulter' => ['defaulter', []],
        'inactive' => ['inactive', []],
        'pending' => ['pending', []],
    ]);

    it('keeps a canceled personal subscription until the paid period ends', function (): void {
        companySubscription($this->company, 'active');
        employeePlanSubscription($this->employee, 'canceled', attributes: ['ends_at' => now()->addWeek()]);

        expect(benefitAccess($this->employee, $this->company))->toBe(BenefitAccessEnum::PersonalPlan);
    });

    it('looks across providers: company on one, employee on another', function (): void {
        $manager = Mockery::mock(BillingManager::class);
        $manager->shouldReceive('getDriver')->with(BillingProviderEnum::Stripe)->andReturn(new FakeBillingContract);
        $manager->shouldReceive('getDriver')->with(BillingProviderEnum::Barte)->andReturn(new FakeBillingContract(hasActiveSubscription: true));
        $manager->shouldReceive('getDriver')->with(BillingProviderEnum::Virtu)->andReturn(new FakeBillingContract(isSubscribed: true));
        employeePlan();

        $access = new ResolveBenefitAccess(resolve(PlanRepository::class), $manager)->for($this->employee, $this->company);

        expect($access)->toBe(BenefitAccessEnum::PersonalPlan);
    });
});

describe('default company', function (): void {
    it('never reports the company plan as inactive, since the default company does not pay', function (): void {
        $default = benefitDefaultCompany($this->employee);

        expect(benefitAccess($this->employee, $default))->toBe(BenefitAccessEnum::PersonalPlanRequired);
    });

    it('grants a standalone-price subscription', function (): void {
        $default = benefitDefaultCompany($this->employee);
        employeePlanSubscription($this->employee, 'active', PriceAudienceEnum::Standalone);

        expect(benefitAccess($this->employee, $default))->toBe(BenefitAccessEnum::PersonalPlan);
    });

    it('refuses a subsidized-price subscription', function (): void {
        $default = benefitDefaultCompany($this->employee);
        employeePlanSubscription($this->employee, 'active', PriceAudienceEnum::Subsidized);

        expect(benefitAccess($this->employee, $default))->toBe(BenefitAccessEnum::PersonalPlanRequired);
    });

    it('refuses a standalone-price subscription that is not active', function (string $status): void {
        $default = benefitDefaultCompany($this->employee);
        employeePlanSubscription($this->employee, $status, PriceAudienceEnum::Standalone);

        expect(benefitAccess($this->employee, $default))->toBe(BenefitAccessEnum::PersonalPlanRequired);
    })->with(['canceled', 'past_due', 'trialing']);

    it('grants a live voucher and refuses a lapsed one', function (string $expiresIn, BenefitAccessEnum $expected): void {
        $default = benefitDefaultCompany($this->employee);
        benefitVoucherCredit($this->employee, $default, ['expires_at' => now()->modify($expiresIn)]);

        expect(benefitAccess($this->employee, $default))->toBe($expected);
    })->with([
        'live' => ['+1 month', BenefitAccessEnum::PersonalPlan],
        'lapsed' => ['-1 day', BenefitAccessEnum::PersonalPlanRequired],
    ]);

    it('still prefers a contract on the default company', function (): void {
        $default = benefitDefaultCompany($this->employee);
        CompanyPlan::factory()->active()->for($default)->create();

        expect(benefitAccess($this->employee, $default))->toBe(BenefitAccessEnum::ContractualPlan);
    });
});

describe('panel parity', function (): void {
    /**
     * O middleware do painel tem que decidir exatamente o que a action decide: liberado passa,
     * plano da empresa inativo vai para a página dela, o resto vai para a vitrine.
     */
    it('makes the panel middleware follow the action', function (Closure $arrange): void {
        $tenant = $arrange($this->employee, $this->company);
        $expected = benefitAccess($this->employee, $tenant);

        filament()->setCurrentPanel(FilamentPanel::User->value);
        actingAs($this->employee->fresh());
        filament()->setTenant($tenant->fresh());

        $response = resolve(RedirectUserIfNotSubscribed::class)
            ->handle(Request::create('/app/test'), fn (): Response => new Response('ok'));

        $outcome = match (true) {
            $response->getContent() === 'ok' => 'granted',
            str_contains((string) $response->headers->get('Location'), 'company-plan-inactive') => BenefitAccessEnum::CompanyPlanInactive->value,
            str_contains((string) $response->headers->get('Location'), 'available-subscriptions') => BenefitAccessEnum::PersonalPlanRequired->value,
            default => 'unexpected',
        };

        expect($outcome)->toBe($expected->isGranted() ? 'granted' : $expected->value);
    })->with([
        'contract' => [fn (User $e, Company $c): Company => tap($c, fn () => CompanyPlan::factory()->active()->for($c)->create())],
        'expired contract' => [fn (User $e, Company $c): Company => tap($c, fn () => CompanyPlan::factory()->expired()->for($c)->create())],
        'company unpaid' => [fn (User $e, Company $c): Company => $c],
        'company paid, employee none' => [function (User $e, Company $c): Company {
            companySubscription($c, 'active');
            employeePlan();

            return $c;
        }],
        'company paid, employee paid' => [function (User $e, Company $c): Company {
            companySubscription($c, 'active');
            employeePlanSubscription($e, 'active');

            return $c;
        }],
        'default, nothing' => [fn (User $e, Company $c): Company => benefitDefaultCompany($e)],
        'default, standalone' => [function (User $e, Company $c): Company {
            employeePlanSubscription($e, 'active', PriceAudienceEnum::Standalone);

            return benefitDefaultCompany($e);
        }],
        'default, subsidized' => [function (User $e, Company $c): Company {
            employeePlanSubscription($e, 'active', PriceAudienceEnum::Subsidized);

            return benefitDefaultCompany($e);
        }],
        'default, voucher' => [function (User $e, Company $c): Company {
            $default = benefitDefaultCompany($e);
            benefitVoucherCredit($e, $default, ['expires_at' => now()->addMonth()]);

            return $default;
        }],
    ]);

    it('creates the checkout customer only when the decision is about a personal plan', function (Closure $arrange, bool $createsCustomer): void {
        $tenant = $arrange($this->employee, $this->company);

        $driver = Mockery::mock(BillingContract::class);
        $driver->shouldReceive('isSubscribed', 'hasActiveSubscription', 'hasActivePlan')->andReturnFalse();
        $driver->shouldReceive('ensureCustomerExists')->times($createsCustomer ? count(BillingProviderEnum::checkoutCases()) : 0);
        $manager = Mockery::mock(BillingManager::class);
        $manager->shouldReceive('getDriver')->andReturn($driver);
        $middleware = new RedirectUserIfNotSubscribed(resolve(ResolveBenefitAccess::class), $manager);

        filament()->setCurrentPanel(FilamentPanel::User->value);
        actingAs($this->employee->fresh());
        filament()->setTenant($tenant->fresh());

        $middleware->handle(Request::create('/app/test'), fn (): Response => new Response('ok'));
    })->with([
        'contract' => [fn (User $e, Company $c): Company => tap($c, fn () => CompanyPlan::factory()->active()->for($c)->create()), false],
        'company unpaid' => [fn (User $e, Company $c): Company => $c, false],
        'company paid, employee none' => [function (User $e, Company $c): Company {
            companySubscription($c, 'active');

            return $c;
        }, true],
        'default, nothing' => [fn (User $e, Company $c): Company => benefitDefaultCompany($e), true],
    ]);
});
