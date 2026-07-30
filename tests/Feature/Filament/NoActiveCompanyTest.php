<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * ÉPICO 56 — a collaborator who reaches the app panel without any active
 * company (e.g. an inactive membership) sees a friendly "no active company"
 * page instead of a bare 404. Admins keep the pre-existing behavior (#178).
 */
it('shows the no-active-company page to an employee whose membership is inactive', function (): void {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::Employee->value);

    $company = Company::factory()->createOne();
    $company->employees()->attach($employee->getKey(), [
        'role' => Roles::Employee->value,
        'active' => false,
    ]);

    actingAs($employee);

    get('/app')
        ->assertOk()
        ->assertSee(__('views.no_company.heading'));
});

it('keeps the pre-existing behavior for admins (lands on a company)', function (): void {
    $owner = User::factory()->companyOwner()->create();
    Company::factory()->recycle($owner)->create(['slug' => 'acme-noactive']);

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    // Admin sees every company (#178), so they are routed into one, not the page.
    get('/app')->assertStatus(302);
});
