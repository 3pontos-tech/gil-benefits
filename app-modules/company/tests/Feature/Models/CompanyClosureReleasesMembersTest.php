<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

it('soft-deletes the live memberships when the company is closed, and frees the person', function (): void {
    $person = defaultCompanyUser();
    $closed = Company::factory()->create();
    $closed->employees()->attach($person->getKey());

    $closed->delete();

    expect(DB::table('company_employees')->where('company_id', $closed->getKey())->whereNull('deleted_at')->count())->toBe(0)
        ->and(User::query()->findOrFail($person->getKey())->employerCompanyId())->toBe(Company::defaultId());

    $next = Company::factory()->create();
    $next->employees()->attach($person->getKey());

    expect(User::query()->findOrFail($person->getKey())->employerCompanyId())->toBe($next->getKey());
});

it('restores exactly the memberships it closed, keeping their flags', function (): void {
    $company = Company::factory()->create();
    $active = defaultCompanyUser();
    $inactive = defaultCompanyUser();
    $removedBefore = defaultCompanyUser();
    $company->employees()->attach($active->getKey(), ['role' => Roles::CompanyManager->value]);
    $company->employees()->attach($inactive->getKey(), ['active' => false]);
    $company->employees()->attach($removedBefore->getKey());
    DB::table('company_employees')->where('company_id', $company->getKey())->where('user_id', $removedBefore->getKey())->update(['deleted_at' => now()->subDay()]);

    $company->delete();
    $company->restore();

    $rows = DB::table('company_employees')->where('company_id', $company->getKey())->get()->keyBy('user_id');

    expect($rows[$active->getKey()]->deleted_at)->toBeNull()
        ->and((bool) $rows[$active->getKey()]->active)->toBeTrue()
        ->and($rows[$active->getKey()]->role)->toBe(Roles::CompanyManager->value)
        ->and($rows[$inactive->getKey()]->deleted_at)->toBeNull()
        ->and((bool) $rows[$inactive->getKey()]->active)->toBeFalse()
        ->and($rows[$removedBefore->getKey()]->deleted_at)->not->toBeNull();
});
