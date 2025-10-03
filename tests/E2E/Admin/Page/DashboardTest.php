<?php

use App\Models\Users\User;
use TresPontosTech\Tenant\Models\Company;

use function Pest\Laravel\actingAs;

it('should load the statuses on dashboard page', function (): void {
    $admin = User::factory()->admin()->create();
    Company::factory()->recycle($admin)->create();
    actingAs($admin);

    $page = visit('/admin');
    $page->assertSee('Active Plans 0 Current active plans');
    $page->assertSee('New Users 1 This week');
    $page->assertSee('Total Companies 1 Overall');
})->skipOnCI();
it('should list latest companies on the chart', function (): void {
    $admin = User::factory()->admin()->create();
    Company::factory()->recycle($admin)->create();
    $companies = Company::factory(9)->create();
    $allCompanies = Company::all()->count();
    actingAs($admin);

    $page = visit('/admin');
    $page->assertSee("Total Companies $allCompanies Overall");

    $companies->each(function ($company) use ($page) {
        $page->assertSee($company->name);
        $page->assertSee($company->tax_id);
    });
})->skipOnCI();
