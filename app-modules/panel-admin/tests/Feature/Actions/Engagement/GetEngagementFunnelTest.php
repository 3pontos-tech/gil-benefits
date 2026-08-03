<?php

declare(strict_types=1);

use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetEngagementFunnel;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetEngagementTotals;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo('2026-07-20 09:00:00');
});

function engagementFilters(?array $companyIds = null): EngagementFilters
{
    return new EngagementFilters(
        start: CarbonImmutable::parse('2026-07-01')->startOfDay(),
        end: CarbonImmutable::parse('2026-07-31')->endOfDay(),
        companyIds: $companyIds ?? [],
    );
}

it('builds the funnel steps and conversion rates of a company', function (): void {
    $company = Company::factory()->create(['name' => 'Acme']);
    CompanyPlan::factory()->active()->create(['company_id' => $company->id, 'seats' => 10]);

    $employees = User::factory()->count(4)->create();
    $company->employees()->attach($employees->pluck('id')->all());

    [$recurrent, $completedOnce, $bookedOnly] = [$employees[0], $employees[1], $employees[2]];

    Appointment::factory()->count(2)->create([
        'company_id' => $company->id,
        'user_id' => $recurrent->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::parse('2026-07-10'),
    ]);

    Appointment::factory()->create([
        'company_id' => $company->id,
        'user_id' => $completedOnce->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::parse('2026-07-11'),
    ]);

    Appointment::factory()->create([
        'company_id' => $company->id,
        'user_id' => $bookedOnly->id,
        'status' => AppointmentStatus::Cancelled,
        'appointment_at' => CarbonImmutable::parse('2026-07-12'),
    ]);

    $rows = resolve(GetEngagementFunnel::class)->handle(engagementFilters([$company->id]));

    expect($rows)->toHaveCount(1);

    $row = $rows->first();

    expect($row->companyName)->toBe('Acme')
        ->and($row->seats)->toBe(10)
        ->and($row->registered)->toBe(4)
        ->and($row->withAppointment)->toBe(3)
        ->and($row->withCompletedAppointment)->toBe(2)
        ->and($row->withRecurrence)->toBe(1)
        ->and($row->registrationRate())->toBe(40.0)
        ->and($row->schedulingRate())->toBe(75.0)
        ->and($row->completionRate())->toBe(66.7)
        ->and($row->recurrenceRate())->toBe(50.0);
});

it('ignores appointments outside the filtered period', function (): void {
    $company = Company::factory()->create();
    $employee = User::factory()->create();
    $company->employees()->attach($employee->id);

    Appointment::factory()->create([
        'company_id' => $company->id,
        'user_id' => $employee->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::parse('2026-06-15'),
    ]);

    $row = resolve(GetEngagementFunnel::class)->handle(engagementFilters([$company->id]))->first();

    expect($row->withAppointment)->toBe(0)
        ->and($row->withCompletedAppointment)->toBe(0)
        ->and($row->completionRate())->toBeNull();
});

it('keeps companies without registered beneficiaries with zeroed values', function (): void {
    $company = Company::factory()->create(['name' => 'Empty Co']);

    $row = resolve(GetEngagementFunnel::class)->handle(engagementFilters([$company->id]))->first();

    expect($row->companyName)->toBe('Empty Co')
        ->and($row->seats)->toBe(0)
        ->and($row->registered)->toBe(0)
        ->and($row->withAppointment)->toBe(0)
        ->and($row->registrationRate())->toBeNull()
        ->and($row->schedulingRate())->toBeNull();
});

it('excludes the company owner from the registered beneficiaries', function (): void {
    $owner = User::factory()->create();
    $company = Company::factory()->recycle($owner)->create();
    $company->employees()->attach($owner->id);

    $employee = User::factory()->create();
    $company->employees()->attach($employee->id);

    $row = resolve(GetEngagementFunnel::class)->handle(engagementFilters([$company->id]))->first();

    expect($row->registered)->toBe(1);
});

it('narrows the funnel down to the selected companies', function (): void {
    $selected = Company::factory()->create(['name' => 'Selected']);
    Company::factory()->create(['name' => 'Other']);

    $rows = resolve(GetEngagementFunnel::class)->handle(engagementFilters([$selected->id]));

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->companyName)->toBe('Selected');
});

it('consolidates the funnel of every company into the totals', function (): void {
    $companies = Company::factory()->count(2)->create();

    foreach ($companies as $company) {
        CompanyPlan::factory()->active()->create(['company_id' => $company->id, 'seats' => 5]);

        $employee = User::factory()->create();
        $company->employees()->attach($employee->id);

        Appointment::factory()->create([
            'company_id' => $company->id,
            'user_id' => $employee->id,
            'status' => AppointmentStatus::Completed,
            'appointment_at' => CarbonImmutable::parse('2026-07-05'),
        ]);
    }

    $totals = resolve(GetEngagementTotals::class)->handle(
        engagementFilters($companies->pluck('id')->all()),
    );

    expect($totals->seats)->toBe(10)
        ->and($totals->registered)->toBe(2)
        ->and($totals->withAppointment)->toBe(2)
        ->and($totals->withCompletedAppointment)->toBe(2)
        ->and($totals->completionRate())->toBe(100.0)
        ->and($totals->recurrenceRate())->toBe(0.0);
});
