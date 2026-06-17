<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Actions\Metrics\ResolveScopedUserIds;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;

it('returns null when no scope is applied', function (): void {
    $company = Company::factory()->create();

    expect(resolve(ResolveScopedUserIds::class)->handle($company, MetricsFilters::none()))->toBeNull();
});

it('returns the single user id when filtering by user', function (): void {
    $company = Company::factory()->create();

    $ids = resolve(ResolveScopedUserIds::class)->handle($company, new MetricsFilters(userId: '42'));

    expect($ids->all())->toBe(['42']);
});

it('resolves department members when filtering by department', function (): void {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $member = User::factory()->create();
    $company->employees()->attach($member->id, ['active' => true, 'department_id' => $department->id]);

    $ids = resolve(ResolveScopedUserIds::class)->handle($company, new MetricsFilters(departmentId: (string) $department->id));

    expect($ids->all())->toContain($member->id);
});
