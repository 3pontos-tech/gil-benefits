<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelCompany\Actions\Metrics\GetTopConsultants;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('ranks consultants by session count within the window', function (): void {
    $company = Company::factory()->create();
    $busy = Consultant::factory()->create();
    $quiet = Consultant::factory()->create();

    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'consultant_id' => $busy->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);
    Appointment::factory()->create([
        'company_id' => $company->id,
        'consultant_id' => $quiet->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    $rows = resolve(GetTopConsultants::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->sessions)->toBe(3)
        ->and($rows[0]->barWidthPercent)->toBe(100.0)
        ->and($rows[1]->sessions)->toBe(1);
});

it('scopes ratings to the filtered users', function (): void {
    $company = Company::factory()->create();
    $consultant = Consultant::factory()->create();
    $inScope = User::factory()->create();
    $outScope = User::factory()->create();

    $inAppt = Appointment::factory()->create([
        'company_id' => $company->id,
        'consultant_id' => $consultant->id,
        'user_id' => $inScope->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);
    $outAppt = Appointment::factory()->create([
        'company_id' => $company->id,
        'consultant_id' => $consultant->id,
        'user_id' => $outScope->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    AppointmentFeedback::factory()->create(['appointment_id' => $inAppt->id, 'rating' => 5]);
    AppointmentFeedback::factory()->create(['appointment_id' => $outAppt->id, 'rating' => 1]);

    $rows = resolve(GetTopConsultants::class)->handle(
        $company,
        MetricsPeriod::lastMonths(12),
        new MetricsFilters(userId: (string) $inScope->id),
    );

    // Only the in-scope session counts, and the rating must reflect only that user.
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->sessions)->toBe(1)
        ->and($rows[0]->rating)->toBe(5.0);
});

it('excludes soft-deleted appointments from session counts and ratings', function (): void {
    $company = Company::factory()->create();
    $consultant = Consultant::factory()->create();

    $live = Appointment::factory()->create([
        'company_id' => $company->id,
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);
    $deleted = Appointment::factory()->create([
        'company_id' => $company->id,
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    AppointmentFeedback::factory()->create(['appointment_id' => $live->id, 'rating' => 5]);
    AppointmentFeedback::factory()->create(['appointment_id' => $deleted->id, 'rating' => 1]);

    $deleted->delete();

    $rows = resolve(GetTopConsultants::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->sessions)->toBe(1)
        ->and($rows[0]->rating)->toBe(5.0);
});
