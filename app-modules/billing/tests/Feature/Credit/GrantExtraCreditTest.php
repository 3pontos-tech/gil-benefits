<?php

declare(strict_types=1);

use App\Models\Users\User;
use Pest\Expectation;
use TresPontosTech\Billing\Core\Actions\Credit\GrantExtraCredit;
use TresPontosTech\Billing\Core\DTOs\GrantCreditDTO;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Exceptions\CannotGrantCreditException;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

function grantExtraCredit(GrantCreditDTO $dto): CreditGrant
{
    return resolve(GrantExtraCredit::class)->handle($dto);
}

it('grants extra credits to a company into the owner pool', function (): void {
    $admin = User::factory()->create();
    $company = Company::factory()->create();

    $grant = grantExtraCredit(new GrantCreditDTO(
        adminUserId: (string) $admin->getKey(),
        company: $company,
        quantity: 3,
        justification: 'Cortesia por atraso no onboarding',
    ));

    expect($grant->target_user_id)->toBeNull()
        ->and($grant->quantity)->toBe(3)
        ->and($grant->admin_user_id)->toBe((string) $admin->getKey()); // granter tracked on the grant

    $credits = UserCredit::query()->where('grant_id', $grant->getKey())->get();

    expect($credits)->toHaveCount(3);
    $credits->each(function (UserCredit $credit) use ($company): void {
        // Owner is the company owner (pool), not the granting admin.
        expect($credit->holder_id)->toBe($company->user_id)
            ->and($credit->owner_id)->toBe($company->user_id)
            ->and($credit->status)->toBe(UserCreditStatusEnum::Available)
            ->and($credit->transferred_at)->toBeNull(); // pool: not yet distributed
    });
});

it('grants extra credits directly to a user, held by the user', function (): void {
    $admin = User::factory()->create();
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey());

    $grant = grantExtraCredit(new GrantCreditDTO(
        adminUserId: (string) $admin->getKey(),
        company: $company,
        quantity: 2,
        justification: 'Compensação de sessão cancelada',
        targetUser: $user,
    ));

    expect($grant->target_user_id)->toBe((string) $user->getKey());

    $credits = UserCredit::query()->where('grant_id', $grant->getKey())->get();

    expect($credits)->toHaveCount(2);
    // Personal gift: owner and holder are the user; not a pool distribution.
    $credits->each(fn (UserCredit $credit): Expectation => expect($credit->holder_id)->toBe((string) $user->getKey())
        ->and($credit->owner_id)->toBe((string) $user->getKey())
        ->and($credit->transferred_at)->toBeNull());
});

it('rejects a quantity of zero or less and creates nothing', function (): void {
    $admin = User::factory()->create();
    $company = Company::factory()->create();

    expect(fn (): CreditGrant => grantExtraCredit(new GrantCreditDTO(
        adminUserId: (string) $admin->getKey(),
        company: $company,
        quantity: 0,
        justification: 'invalid',
    )))->toThrow(CannotGrantCreditException::class);

    expect(CreditGrant::query()->count())->toBe(0)
        ->and(UserCredit::query()->count())->toBe(0);
});

it('rejects an empty justification', function (): void {
    $admin = User::factory()->create();
    $company = Company::factory()->create();

    expect(fn (): CreditGrant => grantExtraCredit(new GrantCreditDTO(
        adminUserId: (string) $admin->getKey(),
        company: $company,
        quantity: 5,
        justification: '   ',
    )))->toThrow(CannotGrantCreditException::class);

    expect(UserCredit::query()->count())->toBe(0);
});

it('preserves the grant as audit when the granting admin is force-deleted', function (): void {
    $admin = User::factory()->create();
    $company = Company::factory()->create();

    $grant = grantExtraCredit(new GrantCreditDTO(
        adminUserId: (string) $admin->getKey(),
        company: $company,
        quantity: 2,
        justification: 'Cortesia',
    ));

    $admin->forceDelete();

    // The grant survives (nullOnDelete), keeping the donation history.
    expect(CreditGrant::query()->find($grant->getKey()))->not->toBeNull()
        ->and($grant->fresh()->admin_user_id)->toBeNull();
});
