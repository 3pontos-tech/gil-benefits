<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Api\Http\Middleware\ForceJsonAndLocale;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('rejects a request without a token', function (): void {
    getJson(route('api.v1.me.show'))
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('answers in JSON even when the client does not ask for it', function (): void {
    get(route('api.v1.me.show'))
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('does not fall back to the web session', function (): void {
    actingAs(User::factory()->employee()->create());

    getJson(route('api.v1.me.show'))->assertUnauthorized();
});

it('rejects a token without the employee ability', function (): void {
    $employee = actingAsEmployee();
    Sanctum::actingAs($employee, ['company']);

    getJson(route('api.v1.me.show'))->assertForbidden();
});

it('lets a company employee in', function (): void {
    $employee = actingAsApiEmployee();

    getJson(route('api.v1.me.show'))
        ->assertOk()
        ->assertJsonPath('data.id', $employee->id)
        ->assertJsonPath('data.email', $employee->email);
});

it('lets an individual subscriber linked only to the default company in', function (): void {
    $subscriber = defaultCompanyUser();

    Sanctum::actingAs($subscriber, ['employee']);

    getJson(route('api.v1.me.show'))
        ->assertOk()
        ->assertJsonPath('data.id', $subscriber->id);
});

it('keeps out accounts that are not the app audience', function (User $user): void {
    Sanctum::actingAs($user, ['employee']);

    getJson(route('api.v1.me.show'))
        ->assertForbidden()
        ->assertJsonPath('message', 'Esta conta não tem acesso ao aplicativo.');
})->with([
    'admin without a company link' => fn (): User => User::factory()->admin()->create(),
    'consultant linked to the default company' => function (): User {
        $consultant = User::factory()->consultant()->create();
        resolve(AttachToDefaultCompany::class)->execute($consultant, Roles::Consultant);

        return $consultant;
    },
    'employee with an inactive link' => function (): User {
        $employee = actingAsEmployee();
        $employee->companies()->updateExistingPivot($employee->companies()->value('companies.id'), ['active' => false]);

        return $employee;
    },
]);

it('throttles each employee separately', function (): void {
    config()->set('api.rate_limits.employee_per_minute', 2);
    actingAsApiEmployee();

    getJson(route('api.v1.me.show'))->assertOk();
    getJson(route('api.v1.me.show'))->assertOk();
    getJson(route('api.v1.me.show'))->assertTooManyRequests();

    $otherEmployee = defaultCompanyUser();
    Sanctum::actingAs($otherEmployee, ['employee']);

    getJson(route('api.v1.me.show'))->assertOk();
});

it('returns validation errors in Portuguese', function (): void {
    Route::middleware(ForceJsonAndLocale::class)
        ->post('api/v1/_locale-probe', fn (Request $request): array => $request->validate(['email' => ['required', 'email']]));

    app()->setLocale('en');

    postJson('api/v1/_locale-probe', ['email' => 'not-an-email'], ['Accept-Language' => 'en'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'O campo email deve ser um endereço de e-mail válido.']);
});
