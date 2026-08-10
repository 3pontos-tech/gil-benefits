<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetWeeklyEngagement;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo('2026-07-20 09:00:00');
});

function weeklyFilters(?array $companyIds = null): EngagementFilters
{
    return new EngagementFilters(
        start: CarbonImmutable::parse('2026-07-06')->startOfDay(),
        end: CarbonImmutable::parse('2026-07-19')->endOfDay(),
        companyIds: $companyIds ?? [],
    );
}

it('buckets booked and held consultancies per week', function (): void {
    $company = Company::factory()->create();

    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::parse('2026-07-08 10:00'),
    ]);

    Appointment::factory()->count(2)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Cancelled,
        'appointment_at' => CarbonImmutable::parse('2026-07-09 10:00'),
    ]);

    $weeks = resolve(GetWeeklyEngagement::class)->handle(weeklyFilters());

    expect($weeks)->toHaveCount(2);

    $first = $weeks->first();

    expect($first->label())->toBe('06/07 – 12/07')
        ->and($first->scheduled)->toBe(5)
        ->and($first->completed)->toBe(3)
        ->and($first->completionRate())->toBe(60.0);
});

it('keeps weeks without activity zeroed instead of omitting them', function (): void {
    $company = Company::factory()->create();

    Appointment::factory()->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::parse('2026-07-15 10:00'),
    ]);

    $weeks = resolve(GetWeeklyEngagement::class)->handle(weeklyFilters());

    expect($weeks)->toHaveCount(2)
        ->and($weeks[0]->scheduled)->toBe(0)
        ->and($weeks[0]->completed)->toBe(0)
        ->and($weeks[0]->completionRate())->toBeNull()
        ->and($weeks[1]->scheduled)->toBe(1);
});

it('only counts the selected companies', function (): void {
    $selected = Company::factory()->create();
    $other = Company::factory()->create();

    Appointment::factory()->create([
        'company_id' => $selected->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::parse('2026-07-08 10:00'),
    ]);

    Appointment::factory()->count(4)->create([
        'company_id' => $other->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::parse('2026-07-08 10:00'),
    ]);

    $weeks = resolve(GetWeeklyEngagement::class)->handle(weeklyFilters([$selected->id]));

    expect($weeks->first()->scheduled)->toBe(1);
});
