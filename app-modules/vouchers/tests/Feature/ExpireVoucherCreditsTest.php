<?php

declare(strict_types=1);

use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Vouchers\Actions\ExpireVoucherCredits;
use TresPontosTech\Vouchers\Database\Factories\VoucherRedemptionFactory;
use TresPontosTech\Vouchers\Jobs\ExpireVoucherCreditsJob;

function voucherCredit(array $state = []): UserCredit
{
    return UserCredit::factory()->create([
        'voucher_redemption_id' => VoucherRedemptionFactory::new()->create()->getKey(),
        ...$state,
    ]);
}

function expireCredits(): int
{
    return resolve(ExpireVoucherCredits::class)->handle();
}

it('expires an available credit past its date', function (): void {
    $credit = voucherCredit(['expires_at' => now()->subDay()]);

    expect(expireCredits())->toBe(1)
        ->and($credit->fresh()->status)->toBe(UserCreditStatusEnum::Expired);
});

it('spares a credit still inside its window', function (): void {
    $credit = voucherCredit(['expires_at' => now()->addDay()]);

    expect(expireCredits())->toBe(0)
        ->and($credit->fresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('spares a credit without a date', function (): void {
    $credit = voucherCredit(['expires_at' => null]);

    expireCredits();

    expect($credit->fresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('spares a credit already tied to an appointment', function (): void {
    $credit = voucherCredit([
        'expires_at' => now()->subDay(),
        'status' => UserCreditStatusEnum::InUse,
    ]);

    expireCredits();

    expect($credit->fresh()->status)->toBe(UserCreditStatusEnum::InUse);
});

it('leaves credits from other origins alone', function (): void {
    $credit = UserCredit::factory()->create(['expires_at' => now()->subDay()]);

    expireCredits();

    expect($credit->fresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('is idempotent', function (): void {
    voucherCredit(['expires_at' => now()->subDay()]);

    expect(expireCredits())->toBe(1)
        ->and(expireCredits())->toBe(0);
});

it('runs from the scheduled job', function (): void {
    $credit = voucherCredit(['expires_at' => now()->subDay()]);

    resolve(ExpireVoucherCreditsJob::class)->handle(resolve(ExpireVoucherCredits::class));

    expect($credit->fresh()->status)->toBe(UserCreditStatusEnum::Expired);
});
