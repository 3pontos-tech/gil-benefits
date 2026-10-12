<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

function employerMembership(User $user, Company $company, bool $active = true, string $since = '2026-01-01 00:00:00'): void
{
    $company->employees()->attach($user->getKey(), ['role' => Roles::Employee->value, 'active' => $active]);

    DB::table('company_employees')
        ->where(['company_id' => $company->getKey(), 'user_id' => $user->getKey()])
        ->update(['created_at' => $since]);
}

beforeEach(function (): void {
    $this->user = User::factory()->employee()->create();
    resolve(AttachToDefaultCompany::class)->execute($this->user, Roles::Employee);
    $this->defaultCompanyId = Company::query()->where('slug', Company::DEFAULT_SLUG)->value('id');
});

it('answers the employer over the default company', function (): void {
    $employer = Company::factory()->create();
    employerMembership($this->user, $employer);

    expect($this->user->employerCompanyId())->toBe($employer->id);
});

it('ignores a company that deactivated the person', function (): void {
    employerMembership($this->user, Company::factory()->create(), active: false);

    expect($this->user->employerCompanyId())->toBe($this->defaultCompanyId);
});

it('answers the active employer when another one deactivated the person', function (): void {
    $former = Company::factory()->create();
    $current = Company::factory()->create();
    employerMembership($this->user, $former, active: false, since: '2024-01-01 00:00:00');
    employerMembership($this->user, $current, since: '2026-01-01 00:00:00');

    expect($this->user->employerCompanyId())->toBe($current->id);
});

it('answers the oldest active employer, whatever the creation order', function (bool $firstIsOlder): void {
    $first = Company::factory()->create();
    $second = Company::factory()->create();
    employerMembership($this->user, $first, since: $firstIsOlder ? '2025-01-01 00:00:00' : '2026-06-01 00:00:00');
    employerMembership($this->user, $second, since: $firstIsOlder ? '2026-06-01 00:00:00' : '2025-01-01 00:00:00');

    expect($this->user->employerCompanyId())->toBe($firstIsOlder ? $first->id : $second->id);
})->with(['first is older' => [true], 'second is older' => [false]]);

it('answers nothing when no membership is active', function (): void {
    DB::table('company_employees')->where('user_id', $this->user->getKey())->update(['active' => false]);

    expect($this->user->employerCompanyId())->toBeNull();
});
