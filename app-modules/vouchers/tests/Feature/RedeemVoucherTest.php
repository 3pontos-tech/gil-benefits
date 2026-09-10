<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Vouchers\Actions\RedeemVoucher;
use TresPontosTech\Vouchers\Events\VoucherRedeemed;
use TresPontosTech\Vouchers\Exceptions\VoucherRedemptionException;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

use function Pest\Laravel\assertDatabaseHas;

function voucherProgram(int $seats = 10): CompanyPlan
{
    return CompanyPlan::factory()->active()->creditsOnly()->create(['seats' => $seats]);
}

function codeFor(CompanyPlan $plan, array $batch = [], array $code = []): VoucherCode
{
    return VoucherCode::factory()
        ->for(VoucherBatch::factory()->forPlan($plan)->state($batch), 'batch')
        ->create($code);
}

function memberOf(string $companyId, Roles $role = Roles::Employee, bool $active = true): User
{
    $user = User::factory()->create();
    $user->companies()->attach($companyId, ['role' => $role->value, 'active' => $active]);

    return $user->fresh();
}

function redeem(User $user, string $code): mixed
{
    return resolve(RedeemVoucher::class)->handle($user, $code);
}

it('issues one credit scoped to the partner company', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $user = memberOf($plan->company_id);

    $redemption = redeem($user, $code->code);

    assertDatabaseHas('user_credits', [
        'holder_id' => $user->getKey(),
        'owner_id' => $user->getKey(),
        'company_id' => $plan->company_id,
        'voucher_redemption_id' => $redemption->getKey(),
        'status' => UserCreditStatusEnum::Available->value,
    ]);

    expect(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(1);
});

it('makes the credit visible to the redeemer under the partner company', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $user = memberOf($plan->company_id);

    redeem($user, $code->code);

    expect($user->fresh()->hasAvailableCredit($plan->company_id))->toBeTrue();
});

it('does not create or change any company membership', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $user = memberOf($plan->company_id);

    redeem($user, $code->code);

    expect($user->fresh()->companies()->count())->toBe(1);
});

it('counts the redemption on the code', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);

    redeem(memberOf($plan->company_id), $code->code);

    expect($code->fresh()->redemptions_count)->toBe(1)
        ->and($code->fresh()->isExhausted())->toBeTrue();
});

it('accepts the code in lower case and with surrounding spaces', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $user = memberOf($plan->company_id);

    redeem($user, '  ' . strtolower($code->code) . ' ');

    expect($user->fresh()->hasAvailableCredit($plan->company_id))->toBeTrue();
});

it('fires an event once the redemption is committed', function (): void {
    Event::fake([VoucherRedeemed::class]);

    $plan = voucherProgram();
    $code = codeFor($plan);

    redeem(memberOf($plan->company_id), $code->code);

    Event::assertDispatched(VoucherRedeemed::class);
});

it('lets a company manager redeem as well', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $manager = memberOf($plan->company_id, Roles::CompanyManager);

    redeem($manager, $code->code);

    expect($manager->fresh()->hasAvailableCredit($plan->company_id))->toBeTrue();
});

it('rejects someone who does not belong to the program company', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);

    redeem(User::factory()->create(), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects a member of another company holding the code', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $outsider = memberOf(Company::factory()->create()->getKey());

    expect(fn (): mixed => redeem($outsider, $code->code))
        ->toThrow(VoucherRedemptionException::class);

    expect(UserCredit::query()->count())->toBe(0);
});

it('rejects a member whose link to the company is inactive', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $former = memberOf($plan->company_id, active: false);

    redeem($former, $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects an unknown code', function (): void {
    redeem(User::factory()->create(), 'ZZZZ-ZZZZ');
})->throws(VoucherRedemptionException::class);

it('rejects a code that reached its redemption limit', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan, code: ['max_redemptions' => 1, 'redemptions_count' => 1]);

    redeem(memberOf($plan->company_id), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects a code past the batch redemption window', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan, batch: ['expires_at' => now()->subDay()]);

    redeem(memberOf($plan->company_id), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects redemption when the program is no longer active', function (): void {
    $plan = CompanyPlan::factory()->creditsOnly()->expired()->create();
    $code = codeFor($plan);

    redeem(memberOf($plan->company_id), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects redemption against a monthly quota plan', function (): void {
    $plan = CompanyPlan::factory()->active()->create();
    $code = codeFor($plan);

    redeem(memberOf($plan->company_id), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects a second redemption from the same batch by the same person', function (): void {
    $plan = voucherProgram();
    $batch = VoucherBatch::factory()->forPlan($plan)->create();
    $first = VoucherCode::factory()->for($batch, 'batch')->create();
    $second = VoucherCode::factory()->for($batch, 'batch')->create();
    $user = memberOf($plan->company_id);

    redeem($user, $first->code);

    expect(fn (): mixed => redeem($user, $second->code))
        ->toThrow(VoucherRedemptionException::class);

    expect(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(1);
});

it('allows redeeming codes from two different batches', function (): void {
    $plan = voucherProgram();
    $first = codeFor($plan);
    $second = codeFor($plan);
    $user = memberOf($plan->company_id);

    redeem($user, $first->code);
    redeem($user, $second->code);

    expect(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(2);
});

it('leaves nothing behind when the redemption fails', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan, batch: ['expires_at' => now()->subDay()]);
    $user = memberOf($plan->company_id);

    try {
        redeem($user, $code->code);
    } catch (VoucherRedemptionException) {
        // esperado
    }

    expect(UserCredit::query()->where('holder_id', $user->getKey())->exists())->toBeFalse()
        ->and($code->fresh()->redemptions_count)->toBe(0);
});
