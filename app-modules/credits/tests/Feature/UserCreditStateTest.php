<?php

declare(strict_types=1);

use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

it('reports a credit past its validity as expired while the column still says available', function (): void {
    $credit = UserCredit::factory()->available()->make(['expires_at' => now()->subMinute()]);

    expect($credit->status)->toBe(UserCreditStatusEnum::Available)
        ->and($credit->effectiveStatus())->toBe(UserCreditStatusEnum::Expired);
});

it('keeps the stored status otherwise', function (UserCreditStatusEnum $status, ?string $expiresAt): void {
    $credit = UserCredit::factory()->make([
        'status' => $status,
        'expires_at' => $expiresAt === null ? null : now()->modify($expiresAt),
    ]);

    expect($credit->effectiveStatus())->toBe($status);
})->with([
    'available without validity' => [UserCreditStatusEnum::Available, null],
    'available still valid' => [UserCreditStatusEnum::Available, '+1 day'],
    'in use past validity' => [UserCreditStatusEnum::InUse, '-1 day'],
    'used past validity' => [UserCreditStatusEnum::Used, '-1 day'],
]);

it('is a company credit only when the owner handed it to someone else', function (): void {
    $own = UserCredit::factory()->make(['owner_id' => 'a', 'holder_id' => 'a']);
    $allocated = UserCredit::factory()->make(['owner_id' => 'owner', 'holder_id' => 'employee']);

    expect($own->isFromCompany())->toBeFalse()
        ->and($allocated->isFromCompany())->toBeTrue();
});
