<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Filament\FilamentPanel;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => artisan('sync:permissions'))
    ->in('Feature', 'E2E', '../app-modules/*/tests');

pest()->group('browser')
    ->in('E2E');

pest()
    ->in('E2E/Admin')
    ->beforeEach(fn () => filament()->setCurrentPanel('admin'));

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function actingAsAdmin(FilamentPanel $panel = FilamentPanel::Admin): User
{
    Artisan::call('sync:permissions');

    $user = User::factory()->admin()->create();
    $user->assignRole(Roles::Admin->value);

    filament()->setCurrentPanel($panel->value);
    actingAs($user);

    return $user;
}

function actingAsSuperAdmin(FilamentPanel $panel = FilamentPanel::Admin): User
{
    Artisan::call('sync:permissions');

    $user = User::factory()->admin()->create();
    $user->assignRole(Roles::SuperAdmin->value);

    filament()->setCurrentPanel($panel->value);
    actingAs($user);

    return $user;
}

function actingAsCompanyOwner(): User
{
    Artisan::call('sync:permissions');

    $user = User::factory()->companyOwner()->create();
    $user->assignRole(Roles::CompanyOwner->value);
    Detail::factory()->recycle($user)->create();
    $company = Company::factory()->recycle($user)->create();
    $company->employees()->attach($user->getKey());
    $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => '12345',
        'stripe_status' => 'active',
        'quantity' => 10,
    ]);
    filament()->setCurrentPanel(FilamentPanel::Company->value);
    actingAs($user);
    filament()->setTenant($company);

    return $user;
}

function actingAsEmployee(): User
{
    Artisan::call('sync:permissions');

    $companyOwner = User::factory()->companyOwner()->create();
    $employee = User::factory()->employee()->create();

    $company = Company::factory()->recycle($companyOwner)->create();
    $company->employees()->attach([$companyOwner->getKey(), $employee->getKey()]);

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

    CompanyPlan::create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => CompanyPlanStatusEnum::Active->value,
        'monthly_appointments_per_employee' => 1,
        'starts_at' => now()->subDay(),
        'seats' => 10,
    ]);

    filament()->setCurrentPanel(FilamentPanel::User->value);
    actingAs($employee);
    filament()->setTenant($company);

    return $employee;
}

function actingAsSubscribedEmployee(int $monthlyLimit = 1): User
{
    Artisan::call('sync:permissions');

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $company->employees()->attach($user->getKey());

    $plan = Plan::factory()->createOne([
        'type' => BillableTypeEnum::User->value,
        'active' => true,
    ]);

    $price = Price::create([
        'billing_plan_id' => $plan->id,
        'billing_scheme' => 'per_unit',
        'tiers_mode' => 'volume',
        'type' => 'recurring',
        'unit_amount_decimal' => 5000,
        'active' => true,
        'provider_price_id' => 'price_user_test',
        'monthly_appointments' => $monthlyLimit,
        'whatsapp_enabled' => true,
        'materials_enabled' => true,
    ]);

    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_user_123',
        'stripe_status' => 'active',
        'stripe_price' => $price->provider_price_id,
        'quantity' => 1,
    ]);

    CompanyPlan::where('company_id', $company->id)->delete();

    filament()->setCurrentPanel('user');
    actingAs($user);
    filament()->setTenant($company);

    return $user;
}

function actingAsConsultant(): Consultant
{
    Artisan::call('sync:permissions');

    $consultant = Consultant::factory()->createOne();

    filament()->setCurrentPanel(FilamentPanel::Consultant->value);
    actingAs($consultant->user);

    Appointment::factory()->recycle($consultant)->count(10)->create();

    return $consultant;
}
