<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Vouchers\Actions\ExpireProgramCredits;
use TresPontosTech\Vouchers\Actions\RedeemVoucher;
use TresPontosTech\Vouchers\Jobs\ExpireProgramCreditsJob;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

function programWithRedeemedCredit(int $seats = 10): array
{
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create(['seats' => $seats]);
    $code = VoucherCode::factory()->for(VoucherBatch::factory()->forPlan($plan), 'batch')->create();
    $user = User::factory()->create();
    $user->companies()->attach($plan->company_id, ['role' => Roles::Employee->value, 'active' => true]);

    resolve(RedeemVoucher::class)->handle($user->fresh(), $code->code);

    return [$plan, $user, UserCredit::query()->where('holder_id', $user->getKey())->sole()];
}

it('expires the unused credits of a closed program', function (): void {
    [$plan, , $credit] = programWithRedeemedCredit();

    $expired = resolve(ExpireProgramCredits::class)->handle($plan);

    expect($expired)->toBe(1)
        ->and($credit->fresh()->status)->toBe(UserCreditStatusEnum::Expired);
});

it('leaves a credit already reserved for an appointment untouched', function (): void {
    [$plan, $user, $credit] = programWithRedeemedCredit();

    $appointment = Appointment::factory()->create([
        'user_id' => $user->getKey(),
        'company_id' => $plan->company_id,
    ]);

    $credit->update([
        'status' => UserCreditStatusEnum::InUse,
        'appointment_id' => $appointment->getKey(),
    ]);

    resolve(ExpireProgramCredits::class)->handle($plan);

    expect($credit->fresh()->status)->toBe(UserCreditStatusEnum::InUse);
});

it('leaves a consumed credit untouched', function (): void {
    [$plan, , $credit] = programWithRedeemedCredit();
    $credit->update(['status' => UserCreditStatusEnum::Used]);

    resolve(ExpireProgramCredits::class)->handle($plan);

    expect($credit->fresh()->status)->toBe(UserCreditStatusEnum::Used);
});

it('does not touch credits from another program', function (): void {
    [, , $otherCredit] = programWithRedeemedCredit();
    $emptyPlan = CompanyPlan::factory()->active()->creditsOnly()->create();

    $expired = resolve(ExpireProgramCredits::class)->handle($emptyPlan);

    expect($expired)->toBe(0)
        ->and($otherCredit->fresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('does not touch credits that did not come from a voucher', function (): void {
    [$plan] = programWithRedeemedCredit();
    $bought = UserCredit::factory()->create(['company_id' => $plan->company_id]);

    resolve(ExpireProgramCredits::class)->handle($plan);

    expect($bought->fresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('is idempotent', function (): void {
    [$plan, , $credit] = programWithRedeemedCredit();

    resolve(ExpireProgramCredits::class)->handle($plan);
    $second = resolve(ExpireProgramCredits::class)->handle($plan);

    expect($second)->toBe(0)
        ->and($credit->fresh()->status)->toBe(UserCreditStatusEnum::Expired);
});

it('sweeps only programs whose window already closed', function (): void {
    [$openPlan, , $openCredit] = programWithRedeemedCredit();
    [$closedPlan, , $closedCredit] = programWithRedeemedCredit();

    $closedPlan->update(['ends_at' => now()->subDay()]);

    resolve(ExpireProgramCreditsJob::class)->handle(resolve(ExpireProgramCredits::class));

    expect($closedCredit->fresh()->status)->toBe(UserCreditStatusEnum::Expired)
        ->and($openCredit->fresh()->status)->toBe(UserCreditStatusEnum::Available)
        ->and($openPlan->fresh()->ends_at)->toBeNull();
});
