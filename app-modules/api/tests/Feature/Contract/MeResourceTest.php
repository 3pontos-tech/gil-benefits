<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Laravel\getJson;

it('exposes every key the app reads from Me', function (): void {
    actingAsApiEmployee();

    getJson(route('api.v1.me.show'))
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'id', 'name', 'email', 'avatar_url', 'phone_number',
            'company' => ['id', 'name', 'slug', 'logo_url'],
            'department', 'member_since', 'anamnese_completed', 'life_moment',
            'last_login_at', 'created_at',
        ]])
        ->assertJsonPath('data.avatar_url', null)
        ->assertJsonPath('data.department', null);
});

it('describes the employer company, department and membership date', function (): void {
    $employee = actingAsApiEmployee();
    $company = Company::query()->findOrFail($employee->employerCompanyId());
    $department = Department::factory()->for($company)->create();

    DB::table('company_employees')
        ->where(['company_id' => $company->id, 'user_id' => $employee->id])
        ->update(['department_id' => $department->id, 'created_at' => '2026-03-01 10:00:00']);

    Detail::factory()->for($employee)->create(['company_id' => $company->id, 'phone_number' => '+5511999990000']);

    getJson(route('api.v1.me.show'))
        ->assertOk()
        ->assertJsonPath('data.company.id', $company->id)
        ->assertJsonPath('data.company.slug', $company->slug)
        ->assertJsonPath('data.department.id', $department->id)
        ->assertJsonPath('data.department.category', $department->category->value)
        ->assertJsonPath('data.department.name', $department->name)
        ->assertJsonPath('data.member_since', '2026-03-01')
        ->assertJsonPath('data.phone_number', '+5511999990000');
});

it('uses the default company for an individual subscriber', function (): void {
    $subscriber = defaultCompanyUser();
    Sanctum::actingAs($subscriber, ['employee']);

    getJson(route('api.v1.me.show'))
        ->assertOk()
        ->assertJsonPath('data.company.slug', Company::DEFAULT_SLUG);
});

it('reports the anamnese state', function (): void {
    $employee = actingAsApiEmployee();

    getJson(route('api.v1.me.show'))
        ->assertJsonPath('data.anamnese_completed', false)
        ->assertJsonPath('data.life_moment', null);

    $anamnese = UserAnamnese::factory()->for($employee)->create();
    Sanctum::actingAs($employee->fresh(), ['employee']);

    getJson(route('api.v1.me.show'))
        ->assertJsonPath('data.anamnese_completed', true)
        ->assertJsonPath('data.life_moment', $anamnese->life_moment->value);
});

it('formats dates as the app expects', function (): void {
    $employee = actingAsApiEmployee();
    $employee->forceFill(['last_login_at' => '2026-10-06 12:12:44'])->save();

    $data = getJson(route('api.v1.me.show'))->json('data');

    expect($data['member_since'])->toMatch('/^\d{4}-\d{2}-\d{2}$/')
        ->and($data['last_login_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/')
        ->and($data['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});
