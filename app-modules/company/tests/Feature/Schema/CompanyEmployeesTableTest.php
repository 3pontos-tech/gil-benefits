<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

it('has a primary key, the partial unique pair, the foreign keys and the user index', function (): void {
    $indexes = collect(Schema::getIndexes('company_employees'));
    $foreignKeys = collect(Schema::getForeignKeys('company_employees'));

    expect($indexes->firstWhere('primary', true)['columns'])->toBe(['id'])
        ->and($indexes->firstWhere('name', 'company_employees_company_id_user_id_unique')['unique'])->toBeTrue()
        ->and($indexes->firstWhere('name', 'company_employees_user_id_index')['columns'])->toBe(['user_id'])
        ->and($foreignKeys->firstWhere('columns', ['company_id'])['foreign_table'])->toBe('companies')
        ->and($foreignKeys->firstWhere('columns', ['company_id'])['on_delete'])->toBe('restrict')
        ->and($foreignKeys->firstWhere('columns', ['user_id'])['foreign_table'])->toBe('users')
        ->and($foreignKeys->firstWhere('columns', ['user_id'])['on_delete'])->toBe('restrict');
});

it('gives every membership an id and defaults the role to employee', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $company->employees()->attach($user->getKey());

    $membership = $company->employees()->firstOrFail()->pivot;

    expect($membership->id)->toBeUuid()
        ->and($membership->role)->toBe(Roles::Employee);
});

it('refuses a membership with an empty role', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $company->employees()->attach($user->getKey(), ['role' => null]);
})->throws(QueryException::class);

it('refuses the same person twice in the same company', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $company->employees()->attach($user->getKey());
    $company->employees()->attach($user->getKey());
})->throws(QueryException::class);

it('refuses a membership pointing at a user that does not exist', function (): void {
    Company::factory()->create()->employees()->attach('019d0000-0000-7000-8000-000000000000');
})->throws(QueryException::class);

it('hides a soft deleted membership from both sides of the relation', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey());

    DB::table('company_employees')->where('user_id', $user->getKey())->update(['deleted_at' => now()]);

    expect($company->employees()->whereKey($user)->exists())->toBeFalse()
        ->and($user->companies()->whereKey($company)->exists())->toBeFalse()
        ->and($user->tenantRole($company))->toBeNull();
});

it('rolls back and reapplies without losing the memberships', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey(), ['role' => Roles::CompanyManager->value]);

    $migration = require base_path('app-modules/company/database/migrations/2026_10_29_000000_harden_company_employees_table.php');

    $migration->down();

    expect(Schema::hasColumn('company_employees', 'id'))->toBeFalse()
        ->and(collect(Schema::getForeignKeys('company_employees'))->firstWhere('columns', ['user_id']))->toBeNull();

    $migration->up();

    expect(Schema::hasColumn('company_employees', 'id'))->toBeTrue()
        ->and($company->employees()->whereKey($user)->firstOrFail()->pivot->role)->toBe(Roles::CompanyManager);
});
