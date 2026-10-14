<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Api\Actions\V1\Employee\ResolveAppAccessAction;
use TresPontosTech\Api\DTOs\AppAccess;
use TresPontosTech\Api\Enums\AppAccessDenial;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

beforeEach(function (): void {
    filament()->setCurrentPanel(null);
    filament()->setTenant(null);
});

function resolveAppAccess(User $user): AppAccess
{
    return resolve(ResolveAppAccessAction::class)->handle($user->fresh());
}

it('grants the employer with a contract and leaves the request tenant alone', function (): void {
    $user = User::factory()->employee()->create();
    $employer = Company::factory()->create();
    CompanyPlan::factory()->active()->for($employer)->create();
    $employer->employees()->attach($user->getKey(), ['role' => Roles::Employee->value]);

    $access = resolveAppAccess($user);

    expect($access->isGranted())->toBeTrue()
        ->and($access->company?->is($employer))->toBeTrue()
        ->and(filament()->getTenant())->toBeNull();
});

it('keeps consultants out even with a paid plan of their own', function (): void {
    $consultant = User::factory()->consultant()->create();
    resolve(AttachToDefaultCompany::class)->execute($consultant, Roles::Consultant);
    subscribeStandalone($consultant);

    expect(resolveAppAccess($consultant)->denial)->toBe(AppAccessDenial::NotAudience);
});

it('keeps out someone without any active membership', function (Closure $arrange): void {
    $user = $arrange();

    expect(resolveAppAccess($user)->denial)->toBe(AppAccessDenial::NotAudience)
        ->and(resolveAppAccess($user)->message())->toBe('Esta conta não tem acesso ao aplicativo.');
})->with([
    'no company at all' => [fn (): User => User::factory()->admin()->create()],
    'only a deactivated membership' => [function (): User {
        $user = User::factory()->employee()->create();
        $employer = Company::factory()->create();
        CompanyPlan::factory()->active()->for($employer)->create();
        $employer->employees()->attach($user->getKey(), ['role' => Roles::Employee->value, 'active' => false]);

        return $user;
    }],
]);

it('gives the reason of the employer when no company grants the benefit', function (): void {
    $user = User::factory()->employee()->create();
    resolve(AttachToDefaultCompany::class)->execute($user, Roles::Employee);
    $employer = Company::factory()->create();
    CompanyPlan::factory()->expired()->for($employer)->create();
    $employer->employees()->attach($user->getKey(), ['role' => Roles::Employee->value]);

    expect(resolveAppAccess($user)->denial)->toBe(AppAccessDenial::CompanyPlanInactive);
});
