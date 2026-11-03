<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Api\Enums\TokenAbility;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Tenant\Exceptions\EmployedElsewhere;
use TresPontosTech\Vouchers\Database\Factories\VoucherRedemptionFactory;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/**
 * Quem pode usar o app segue a régua do painel (ResolveBenefitAccess), e a empresa do app é a
 * primeira ativa onde a pessoa tem o benefício. As pessoas abaixo são as da decisão da 2.7.
 */
beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    Queue::fake();
    Mail::fake();
});

/**
 * Todo cadastro também liga a pessoa à empresa padrão, como em produção.
 */
function registeredPerson(): User
{
    $user = User::factory()->employee()->create();
    resolve(AttachToDefaultCompany::class)->execute($user, Roles::Employee);

    return $user;
}

function employerWithContract(int $perEmployee = 5): Company
{
    $company = Company::factory()->create();
    CompanyPlan::factory()->active()->for($company)->create(['monthly_appointments_per_employee' => $perEmployee]);

    return $company;
}

function joinCompany(User $user, Company $company, bool $active = true, string $since = '2026-01-01 00:00:00', Roles $role = Roles::Employee): void
{
    $company->employees()->attach($user->getKey(), ['role' => $role->value, 'active' => $active]);

    DB::table('company_employees')
        ->where(['company_id' => $company->getKey(), 'user_id' => $user->getKey()])
        ->update(['created_at' => $since]);
}

function giveLiveVoucher(User $user): void
{
    UserCredit::factory()->create([
        'holder_id' => $user->getKey(),
        'owner_id' => $user->getKey(),
        'company_id' => Company::query()->where('slug', Company::DEFAULT_SLUG)->value('id'),
        'voucher_redemption_id' => VoucherRedemptionFactory::new()->create()->getKey(),
        'expires_at' => now()->addMonth(),
    ]);
}

function defaultCompanyId(): string
{
    return (string) Company::query()->where('slug', Company::DEFAULT_SLUG)->value('id');
}

/**
 * @return array{0: User, 1: Closure(): ?string, 2: ?string}
 */
dataset('people', [
    'Ana: employer with a contract' => [function (): array {
        $ana = registeredPerson();
        $employer = employerWithContract();
        joinCompany($ana, $employer);

        return [$ana, fn (): string => $employer->id, null];
    }],
    'Bruno: employer contract over, no personal plan' => [function (): array {
        $bruno = registeredPerson();
        $employer = Company::factory()->create();
        CompanyPlan::factory()->expired()->for($employer)->create();
        joinCompany($bruno, $employer);

        return [$bruno, fn (): null => null, 'O plano da sua empresa está inativo. Fale com o RH.'];
    }],
    'Carla: signed up and did not pay' => [function (): array {
        return [registeredPerson(), fn (): null => null, 'Para usar o app, ative um plano pelo site.'];
    }],
    'Daniel: deactivated by the employer, own standalone plan' => [function (): array {
        $daniel = registeredPerson();
        joinCompany($daniel, employerWithContract(), active: false);
        subscribeStandalone($daniel, 3);

        return [$daniel, fn (): string => defaultCompanyId(), null];
    }],
    'Fabio: deactivated by the employer, nothing else' => [function (): array {
        $fabio = registeredPerson();
        joinCompany($fabio, employerWithContract(), active: false);

        return [$fabio, fn (): null => null, 'Para usar o app, ative um plano pelo site.'];
    }],
    'Eva: employer without a plan, own standalone plan' => [function (): array {
        $eva = registeredPerson();
        joinCompany($eva, Company::factory()->create());
        subscribeStandalone($eva, 3);

        return [$eva, fn (): string => defaultCompanyId(), null];
    }],
    'voucher holder' => [function (): array {
        $holder = registeredPerson();
        giveLiveVoucher($holder);

        return [$holder, fn (): string => defaultCompanyId(), null];
    }],
]);

it('signs in only who has the benefit, with the reason otherwise', function (Closure $person): void {
    [$user, $expectedCompany, $message] = $person();

    $response = postJson(route('api.v1.auth.login'), ['email' => $user->email, 'password' => 'password', 'device_name' => 'Android']);

    if ($message !== null) {
        $response->assertUnprocessable()->assertJsonValidationErrors(['email' => $message]);
        expect($user->tokens()->count())->toBe(0);

        return;
    }

    $response->assertOk()->assertJsonPath('data.user.company.id', $expectedCompany());
})->with('people');

it('checks the benefit on every request, with the same reason', function (Closure $person): void {
    [$user, $expectedCompany, $message] = $person();
    Sanctum::actingAs($user, [TokenAbility::Employee->value]);

    $response = getJson(route('api.v1.me.show'));

    if ($message !== null) {
        $response->assertForbidden()->assertJsonPath('message', $message);

        return;
    }

    $response->assertOk()->assertJsonPath('data.company.id', $expectedCompany());
})->with('people');

it('keeps consultants out with the generic message', function (): void {
    $consultant = User::factory()->consultant()->create();
    resolve(AttachToDefaultCompany::class)->execute($consultant, Roles::Consultant);
    subscribeStandalone($consultant);

    postJson(route('api.v1.auth.login'), ['email' => $consultant->email, 'password' => 'password', 'device_name' => 'Android'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Esta conta não tem acesso ao aplicativo.']);
});

it('runs a deactivated employee on their own plan, not on the former employer quota (1.1)', function (): void {
    $daniel = registeredPerson();
    joinCompany($daniel, employerWithContract(perEmployee: 5), active: false);
    subscribeStandalone($daniel, 3);
    consultantAvailableOn(Date::parse('2026-10-09'));
    Sanctum::actingAs($daniel, [TokenAbility::Employee->value]);

    getJson(route('api.v1.credits.show'))->assertOk()->assertJsonPath('data.monthly_quota.limit', 3);

    $response = postJson(route('api.v1.appointments.store'), [
        'category_type' => 'personal_finance',
        'appointment_at' => '2026-10-09T09:00:00-03:00',
    ])->assertCreated();

    expect(Appointment::query()->findOrFail($response->json('data.id'))->company_id)->toBe(defaultCompanyId());
});

it('runs an employee of a company without a plan on their own plan', function (): void {
    $eva = registeredPerson();
    $employer = Company::factory()->create();
    joinCompany($eva, $employer);
    subscribeStandalone($eva, 3);
    Sanctum::actingAs($eva, [TokenAbility::Employee->value]);

    getJson(route('api.v1.credits.show'))->assertOk()->assertJsonPath('data.monthly_quota.limit', 3);

    $ticket = postJson(route('api.v1.tickets.store'), ['category' => 'other', 'subject' => 'Assunto', 'description' => 'Texto.'])
        ->assertCreated();

    expect(SupportTicket::query()->findOrFail($ticket->json('data.id'))->company_id)->toBe(defaultCompanyId());
});

it('prefers the employer over the default company when both grant the benefit', function (): void {
    $ana = registeredPerson();
    $employer = employerWithContract(perEmployee: 5);
    joinCompany($ana, $employer);
    subscribeStandalone($ana, 3);
    Sanctum::actingAs($ana, [TokenAbility::Employee->value]);

    getJson(route('api.v1.me.show'))->assertOk()->assertJsonPath('data.company.id', $employer->id);
    getJson(route('api.v1.credits.show'))->assertOk()->assertJsonPath('data.monthly_quota.limit', 5);
});

it('refuses a second active employer instead of guessing between them', function (): void {
    $person = registeredPerson();
    $first = employerWithContract();
    $second = employerWithContract();
    joinCompany($person, $first);

    expect(fn () => joinCompany($person, $second))->toThrow(EmployedElsewhere::class);

    Sanctum::actingAs($person, [TokenAbility::Employee->value]);

    foreach (range(1, 3) as $attempt) {
        getJson(route('api.v1.me.show'))->assertOk()->assertJsonPath('data.company.id', $first->id);
    }
});

it('prefers the employer over a company the person only manages, whatever the dates', function (bool $employerIsOlder): void {
    $person = registeredPerson();
    $employer = employerWithContract();
    $managed = employerWithContract();
    joinCompany($person, $employer, since: $employerIsOlder ? '2025-01-01 00:00:00' : '2026-06-01 00:00:00');
    joinCompany($person, $managed, role: Roles::CompanyManager, since: $employerIsOlder ? '2026-06-01 00:00:00' : '2025-01-01 00:00:00');
    Sanctum::actingAs($person, [TokenAbility::Employee->value]);

    getJson(route('api.v1.me.show'))->assertOk()->assertJsonPath('data.company.id', $employer->id);
})->with(['employer is older' => [true], 'managed is older' => [false]]);

it('falls back to a managed company with the benefit when the employer has none', function (): void {
    $person = registeredPerson();
    $lapsed = Company::factory()->create();
    CompanyPlan::factory()->expired()->for($lapsed)->create();
    $managed = employerWithContract();
    joinCompany($person, $lapsed);
    joinCompany($person, $managed, role: Roles::CompanyManager);
    Sanctum::actingAs($person, [TokenAbility::Employee->value]);

    getJson(route('api.v1.me.show'))->assertOk()->assertJsonPath('data.company.id', $managed->id);
});

it('cuts the session off when the company contract ends after the login', function (): void {
    $ana = registeredPerson();
    $employer = employerWithContract();
    joinCompany($ana, $employer);
    $token = $ana->createToken('Android', [TokenAbility::Employee->value])->plainTextToken;

    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $token])->assertOk();

    CompanyPlan::query()->where('company_id', $employer->id)->update(['status' => CompanyPlanStatusEnum::Inactive]);
    resolve(Factory::class)->forgetGuards();

    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $token])
        ->assertForbidden()
        ->assertJsonPath('message', 'O plano da sua empresa está inativo. Fale com o RH.');

    postJson(route('api.v1.appointments.store'), ['category_type' => 'personal_finance', 'appointment_at' => '2026-10-09T09:00:00-03:00'], ['Authorization' => 'Bearer ' . $token])
        ->assertForbidden();

    expect(Appointment::query()->count())->toBe(0);
});

it('cuts the session off when the employer deactivates the person', function (): void {
    $ana = registeredPerson();
    $employer = employerWithContract();
    joinCompany($ana, $employer);
    $token = $ana->createToken('Android', [TokenAbility::Employee->value])->plainTextToken;

    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $token])->assertOk();

    $employer->employees()->updateExistingPivot($ana->getKey(), ['active' => false]);
    resolve(Factory::class)->forgetGuards();

    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $token])
        ->assertForbidden()
        ->assertJsonPath('message', 'Para usar o app, ative um plano pelo site.');
});
