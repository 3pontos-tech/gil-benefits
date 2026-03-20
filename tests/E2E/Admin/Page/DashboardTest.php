<?php

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;

it('should load the statuses on dashboard page', function (): void {
    $admin = User::factory()->admin()->create();
    Company::factory()->recycle($admin)->create();
    actingAs($admin);

    $page = visit('/admin');
    $page->assertSee(__('panel-admin::widgets.stats_overview.new_users'));
    $page->assertSee(__('panel-admin::widgets.stats_overview.total_companies'));
})->skipOnCI();
it('should list latest companies on the chart', function (): void {
    $admin = User::factory()->admin()->create();
    Company::factory()->recycle($admin)->create();
    $companies = Company::factory(9)->create();
    $allCompanies = Company::all()->count();
    actingAs($admin);

    $page = visit('/admin');
    $page->assertSee(__('panel-admin::widgets.stats_overview.total_companies'));

    $companies->each(function ($company) use ($page) {
        $page->assertSee($company->name);
        $page->assertSee($company->tax_id);
    });
})->skipOnCI();
