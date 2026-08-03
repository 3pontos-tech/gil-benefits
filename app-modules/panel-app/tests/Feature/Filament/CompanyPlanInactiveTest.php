<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * ÉPICO 56 — a member of a company whose plan is inactive (never paid or
 * cancelled) is sent to a friendly "plan inactive" page instead of a bare 403.
 */
beforeEach(function (): void {
    $this->employee = User::factory()->employee()->create();
    $this->company = Company::factory()->createOne();
    $this->company->employees()->attach($this->employee->getKey());

    filament()->setCurrentPanel(FilamentPanel::User->value);
    actingAs($this->employee);
    filament()->setTenant($this->company);
});

it('redirects a member of an unpaid company to the plan-inactive page instead of a 403', function (): void {
    $response = get(route('filament.app.pages.user-dashboard', ['tenant' => $this->company->slug]));

    $response->assertStatus(302);

    expect($response->headers->get('Location'))->toContain('company-plan-inactive');
});

it('renders the plan-inactive page without looping', function (): void {
    get(route('filament.app.pages.company-plan-inactive', ['tenant' => $this->company->slug]))
        ->assertOk()
        ->assertSee($this->company->name);
});
