<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Actions\Metrics\GetDepartmentVolume;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('counts appointments per department in the window', function (): void {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey(), ['department_id' => $department->id]);

    Appointment::factory()->count(2)->create([
        'company_id' => $company->id, 'user_id' => $user->getKey(), 'appointment_at' => now(),
    ]);

    $volume = resolve(GetDepartmentVolume::class)->handle($company, MetricsPeriod::lastMonths(12));

    expect($volume->rows)->toHaveCount(1)
        ->and($volume->rows[0]->total)->toBe(2)
        ->and($volume->rows[0]->name)->toBe($department->name);
});
