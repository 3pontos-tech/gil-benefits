<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Exceptions\EmployedElsewhere;

beforeEach(function (): void {
    $this->person = defaultCompanyUser();
    $this->employer = Company::factory()->create();
    $this->employer->employees()->attach($this->person->getKey());
});

it('refuses a second active employee membership', function (): void {
    $other = Company::factory()->create();

    expect(fn () => $other->employees()->attach($this->person->getKey()))
        ->toThrow(EmployedElsewhere::class, $this->employer->name);

    expect($other->employees()->whereKey($this->person->getKey())->exists())->toBeFalse();
});

it('refuses the second membership through syncWithoutDetaching too', function (): void {
    $other = Company::factory()->create();

    expect(fn () => $other->employees()->syncWithoutDetaching([$this->person->getKey() => ['role' => Roles::Employee->value]]))
        ->toThrow(EmployedElsewhere::class);
});

it('lets the person own or manage another company', function (): void {
    $owned = Company::factory()->create();
    $managed = Company::factory()->create();

    $owned->employees()->attach($this->person->getKey(), ['role' => Roles::CompanyOwner->value]);
    $managed->employees()->attach($this->person->getKey(), ['role' => Roles::CompanyManager->value]);

    expect($this->person->companies()->count())->toBe(4);
});

it('lets an owner elsewhere become an employee here', function (): void {
    $person = defaultCompanyUser();
    Company::factory()->create()->employees()->attach($person->getKey(), ['role' => Roles::CompanyOwner->value]);

    Company::factory()->create()->employees()->attach($person->getKey());

    expect($person->companies()->count())->toBe(3);
});

it('allows a second membership when the first is inactive', function (): void {
    $this->employer->employees()->updateExistingPivot($this->person->getKey(), ['active' => false]);

    Company::factory()->create()->employees()->attach($this->person->getKey());

    expect($this->person->companies()->wherePivot('active', true)->withoutDefault()->count())->toBe(1);
});

it('refuses reactivating a membership while another employer is active', function (): void {
    $this->employer->employees()->updateExistingPivot($this->person->getKey(), ['active' => false]);
    $current = Company::factory()->create();
    $current->employees()->attach($this->person->getKey());

    expect(fn () => $this->employer->employees()->updateExistingPivot($this->person->getKey(), ['active' => true]))
        ->toThrow(EmployedElsewhere::class, $current->name);
});

it('refuses demoting a manager to employee while employed elsewhere', function (): void {
    $other = Company::factory()->create();
    $other->employees()->attach($this->person->getKey(), ['role' => Roles::CompanyManager->value]);

    expect(fn () => $other->employees()->updateExistingPivot($this->person->getKey(), ['role' => Roles::Employee->value]))
        ->toThrow(EmployedElsewhere::class);
});

it('ignores a soft-deleted membership', function (): void {
    $person = defaultCompanyUser();
    $gone = Company::factory()->create();
    $gone->employees()->attach($person->getKey());
    DB::table('company_employees')->where('user_id', $person->getKey())->where('company_id', $gone->getKey())->update(['deleted_at' => now()]);

    $current = Company::factory()->create();
    $current->employees()->attach($person->getKey());

    expect(User::query()->findOrFail($person->getKey())->employerCompanyId())->toBe($current->getKey());
});

it('still counts a membership left live at a soft-deleted company, like the index does', function (): void {
    $person = defaultCompanyUser();
    $closed = Company::factory()->create();
    $closed->employees()->attach($person->getKey());
    DB::table('companies')->where('id', $closed->getKey())->update(['deleted_at' => now()]);

    expect(fn () => Company::factory()->create()->employees()->attach($person->getKey()))
        ->toThrow(EmployedElsewhere::class, $closed->name);
});

it('lets an unrelated change through on a legacy conflicting row', function (): void {
    $other = Company::factory()->create();
    DB::table('company_employees')->insert([
        'id' => (string) Str::uuid7(),
        'company_id' => $other->getKey(),
        'user_id' => $this->person->getKey(),
        'role' => Roles::Employee->value,
        'active' => true,
    ]);

    $department = Department::factory()->for($other)->create();
    $other->employees()->updateExistingPivot($this->person->getKey(), ['department_id' => $department->getKey()]);

    expect($other->employees()->whereKey($this->person->getKey())->first()->pivot->department_id)->toBe($department->getKey());
});

it('never counts the default bucket as an employer', function (): void {
    $person = defaultCompanyUser();

    Company::factory()->create()->employees()->attach($person->getKey());

    expect($person->companies()->count())->toBe(2);
});

it('resolves the employer without looking at membership dates', function (): void {
    $managed = Company::factory()->create();
    $managed->employees()->attach($this->person->getKey(), ['role' => Roles::CompanyManager->value, 'created_at' => now()->addDay()]);

    expect($this->person->employerCompanyId())->toBe($this->employer->getKey());
});
