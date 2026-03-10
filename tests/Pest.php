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
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
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
