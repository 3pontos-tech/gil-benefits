<?php

declare(strict_types=1);

use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

use function Pest\Laravel\getJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
});

it('reports the monthly quota of the company plan and when it renews', function (): void {
    actingAsApiEmployee();

    getJson(route('api.v1.credits.show'))
        ->assertOk()
        ->assertJsonPath('data.monthly_quota', [
            'limit' => 1,
            'left' => 1,
            'renews_at' => '2026-11-06',
        ]);
});

it('lowers what is left once an appointment uses the quota', function (): void {
    $employee = actingAsApiEmployee();
    appointmentFor($employee, AppointmentStatus::Pending, now()->addDays(3));
    Sanctum::actingAs($employee->fresh(), ['employee']);

    getJson(route('api.v1.credits.show'))
        ->assertJsonPath('data.monthly_quota.limit', 1)
        ->assertJsonPath('data.monthly_quota.left', 0);
});

it('answers an empty quota to someone without a plan', function (): void {
    Sanctum::actingAs(defaultCompanyUser(), ['employee']);

    getJson(route('api.v1.credits.show'))
        ->assertOk()
        ->assertJsonPath('data.monthly_quota', ['limit' => 0, 'left' => 0, 'renews_at' => null])
        ->assertJsonPath('data.credits', []);
});

it('lists the credits held in the employer company, newest first, including used and expired', function (): void {
    $employee = actingAsApiEmployee();

    $older = standaloneCreditFor($employee, ['created_at' => now()->subMonths(2)]);
    $used = standaloneCreditFor($employee, [
        'status' => UserCreditStatusEnum::Used,
        'used_at' => now()->subMonth(),
        'created_at' => now()->subMonth(),
    ]);
    $newest = standaloneCreditFor($employee, ['created_at' => now()->subDay()]);

    standaloneCreditFor($employee, ['company_id' => Company::factory()->create()->getKey()]);
    standaloneCreditFor(User::factory()->create(), ['company_id' => $employee->employerCompanyId()]);

    getJson(route('api.v1.credits.show'))
        ->assertOk()
        ->assertJsonPath('data.credits.*.id', [$newest->id, $used->id, $older->id])
        ->assertJsonPath('data.credits.1.status', 'used');
});

it('marks a credit past its validity as expired before the nightly job runs', function (): void {
    $employee = actingAsApiEmployee();
    standaloneCreditFor($employee, ['expires_at' => now()->subHour()]);

    expect(UserCredit::query()->value('status'))->toBe(UserCreditStatusEnum::Available);

    getJson(route('api.v1.credits.show'))
        ->assertJsonPath('data.credits.0.status', 'expired');
});

it('tells company credits apart from the employee own', function (): void {
    $employee = actingAsApiEmployee();
    $companyOwner = Company::query()->findOrFail($employee->employerCompanyId())->owner;

    $allocated = standaloneCreditFor($employee, [
        'owner_id' => $companyOwner->getKey(),
        'transferred_at' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]);
    $own = standaloneCreditFor($employee, ['created_at' => now()->subDays(2)]);

    getJson(route('api.v1.credits.show'))
        ->assertJsonPath('data.credits.0.id', $allocated->id)
        ->assertJsonPath('data.credits.0.owner_type', 'company')
        ->assertJsonPath('data.credits.1.id', $own->id)
        ->assertJsonPath('data.credits.1.owner_type', 'user');
});
