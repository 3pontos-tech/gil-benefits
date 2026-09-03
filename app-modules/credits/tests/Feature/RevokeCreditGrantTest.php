<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Actions\ConsumeCredit;
use TresPontosTech\Credits\Actions\GrantExtraCredit;
use TresPontosTech\Credits\Actions\RevokeCreditGrant;
use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Credits\DTOs\GrantCreditDTO;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\CreditGrant;
use TresPontosTech\Credits\Models\UserCredit;

it('revokes only the still-available credits and keeps consumed ones', function (): void {
    $admin = User::factory()->create();
    $company = Company::factory()->create();

    $grant = resolve(GrantExtraCredit::class)->handle(new GrantCreditDTO(
        adminUserId: (string) $admin->getKey(),
        company: $company,
        quantity: 5,
        justification: 'Cortesia',
    ));

    // Consume two of them (already booked → cannot be undone).
    UserCredit::query()->where('grant_id', $grant->getKey())->limit(2)->get()
        ->each(fn (UserCredit $credit) => $credit->update(['status' => UserCreditStatusEnum::Used]));

    resolve(RevokeCreditGrant::class)->handle($grant->fresh());

    // The 3 available credits are soft-deleted; the 2 used ones remain.
    expect(UserCredit::query()->where('grant_id', $grant->getKey())->count())->toBe(2)
        ->and(UserCredit::onlyTrashed()->where('grant_id', $grant->getKey())->count())->toBe(3);

    // The grant stays as the permanent donation record — revocation lives per credit.
    expect(CreditGrant::query()->find($grant->getKey()))->not->toBeNull();
});

it('keeps grant_id on a donated credit after it is consumed', function (): void {
    $admin = User::factory()->create();
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey());

    $grant = resolve(GrantExtraCredit::class)->handle(new GrantCreditDTO(
        adminUserId: (string) $admin->getKey(),
        company: $company,
        quantity: 1,
        justification: 'Cortesia',
        targetUser: $user,
    ));

    resolve(ConsumeCredit::class)->execute(new CreditDTO(holderId: (string) $user->getKey()));

    $credit = UserCredit::query()->where('grant_id', $grant->getKey())->first();

    expect($credit->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($credit->grant_id)->toBe((string) $grant->getKey());
});
