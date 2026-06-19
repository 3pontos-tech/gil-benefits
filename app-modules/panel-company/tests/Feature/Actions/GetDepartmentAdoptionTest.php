<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Actions\Metrics\GetDepartmentAdoption;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('computes the share of active department members with sessions', function (): void {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $adopter = User::factory()->create();
    $idle = User::factory()->create();
    $company->employees()->attach($adopter->id, ['active' => true, 'department_id' => $department->id]);
    $company->employees()->attach($idle->id, ['active' => true, 'department_id' => $department->id]);

    Appointment::factory()->create([
        'company_id' => $company->id,
        'user_id' => $adopter->id,
        'appointment_at' => now(),
    ]);

    $bars = resolve(GetDepartmentAdoption::class)->handle($company, MetricsPeriod::lastMonths(12));

    expect($bars)->toHaveCount(1)
        ->and($bars[0]->total)->toBe(2)
        ->and($bars[0]->adopted)->toBe(1)
        ->and($bars[0]->percent)->toBe(50.0);
});

it('ignores soft-deleted appointments when counting adoption', function (): void {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $adopter = User::factory()->create();
    $company->employees()->attach($adopter->id, ['active' => true, 'department_id' => $department->id]);

    $appointment = Appointment::factory()->create([
        'company_id' => $company->id,
        'user_id' => $adopter->id,
        'appointment_at' => now(),
    ]);
    $appointment->delete();

    $bars = resolve(GetDepartmentAdoption::class)->handle($company, MetricsPeriod::lastMonths(12));

    expect($bars)->toHaveCount(1)
        ->and($bars[0]->total)->toBe(1)
        ->and($bars[0]->adopted)->toBe(0)
        ->and($bars[0]->percent)->toBe(0.0);
});
