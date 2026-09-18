<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Vouchers\Actions\RedeemVoucher;
use TresPontosTech\Vouchers\Events\VoucherRedeemed;
use TresPontosTech\Vouchers\Exceptions\VoucherRedemptionException;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
    Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);
});

function voucherProgram(array $state = []): CompanyPlan
{
    return CompanyPlan::factory()->active()->creditsOnly()->create($state);
}

function codeFor(CompanyPlan $plan, array $batch = [], array $code = []): VoucherCode
{
    return VoucherCode::factory()
        ->for(VoucherBatch::factory()->forPlan($plan)->state($batch), 'batch')
        ->create($code);
}

function redeem(User $user, string $code): mixed
{
    return resolve(RedeemVoucher::class)->handle($user, $code);
}

it('issues one credit in the default tenant, not in the partner company', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $user = User::factory()->create();

    $redemption = redeem($user, $code->code);

    assertDatabaseHas('user_credits', [
        'holder_id' => $user->getKey(),
        'owner_id' => $user->getKey(),
        'company_id' => Company::default()->getKey(),
        'voucher_redemption_id' => $redemption->getKey(),
        'status' => UserCreditStatusEnum::Available->value,
    ]);

    assertDatabaseMissing('user_credits', ['company_id' => $plan->company_id]);

    expect(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(1);
});

it('keeps the campaign reachable from the credit', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);

    $redemption = redeem(User::factory()->create(), $code->code);

    expect($redemption->credits()->count())->toBe(1)
        ->and($redemption->code->batch->company_id)->toBe($plan->company_id);
});

it('makes the credit usable in the default tenant', function (): void {
    $code = codeFor(voucherProgram());
    $user = User::factory()->create();

    redeem($user, $code->code);

    expect($user->fresh()->hasAvailableCredit(Company::default()->getKey()))->toBeTrue()
        ->and($user->fresh()->holdsLiveVoucher())->toBeTrue();
});

it('does not attach the redeemer to the partner company', function (): void {
    $plan = voucherProgram();
    $code = codeFor($plan);
    $user = User::factory()->create();

    redeem($user, $code->code);

    expect($user->fresh()->companies()->count())->toBe(0);
});

it('stamps the credit with the campaign end date', function (): void {
    $plan = voucherProgram(['ends_at' => now()->addMonths(3)]);
    $code = codeFor($plan);
    $user = User::factory()->create();

    redeem($user, $code->code);

    expect($user->credits()->sole()->expires_at?->toDateString())
        ->toBe($plan->ends_at->toDateString());
});

it('prefers the batch deadline when it falls before the campaign end', function (): void {
    $plan = voucherProgram(['ends_at' => now()->addMonths(3)]);
    $deadline = now()->addMonth();
    $code = codeFor($plan, batch: ['expires_at' => $deadline]);
    $user = User::factory()->create();

    redeem($user, $code->code);

    expect($user->credits()->sole()->expires_at?->toDateString())
        ->toBe($deadline->toDateString());
});

it('leaves the credit open ended when neither carries a date', function (): void {
    $code = codeFor(voucherProgram(['ends_at' => null]), batch: ['expires_at' => null]);
    $user = User::factory()->create();

    redeem($user, $code->code);

    expect($user->credits()->sole()->expires_at)->toBeNull();
});

it('counts the redemption on the code', function (): void {
    $code = codeFor(voucherProgram());

    redeem(User::factory()->create(), $code->code);

    expect($code->fresh()->redemptions_count)->toBe(1)
        ->and($code->fresh()->isExhausted())->toBeTrue();
});

it('accepts the code in lower case and with surrounding spaces', function (): void {
    $code = codeFor(voucherProgram());
    $user = User::factory()->create();

    redeem($user, '  ' . strtolower($code->code) . ' ');

    expect($user->fresh()->holdsLiveVoucher())->toBeTrue();
});

it('fires an event once the redemption is committed', function (): void {
    Event::fake([VoucherRedeemed::class]);

    $code = codeFor(voucherProgram());

    redeem(User::factory()->create(), $code->code);

    Event::assertDispatched(VoucherRedeemed::class);
});

it('rejects an unknown code', function (): void {
    redeem(User::factory()->create(), 'ZZZZ-ZZZZ');
})->throws(VoucherRedemptionException::class);

it('rejects a code that reached its redemption limit', function (): void {
    $code = codeFor(voucherProgram(), code: ['max_redemptions' => 1, 'redemptions_count' => 1]);

    redeem(User::factory()->create(), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects a code past the batch redemption window', function (): void {
    $code = codeFor(voucherProgram(), batch: ['expires_at' => now()->subDay()]);

    redeem(User::factory()->create(), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects redemption when the program is no longer active', function (): void {
    $code = codeFor(CompanyPlan::factory()->creditsOnly()->expired()->create());

    redeem(User::factory()->create(), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects redemption against a monthly quota plan', function (): void {
    $code = codeFor(CompanyPlan::factory()->active()->create());

    redeem(User::factory()->create(), $code->code);
})->throws(VoucherRedemptionException::class);

it('rejects a second redemption from the same batch by the same person', function (): void {
    $batch = VoucherBatch::factory()->forPlan(voucherProgram())->create();
    $first = VoucherCode::factory()->for($batch, 'batch')->create();
    $second = VoucherCode::factory()->for($batch, 'batch')->create();
    $user = User::factory()->create();

    redeem($user, $first->code);

    expect(fn (): mixed => redeem($user, $second->code))
        ->toThrow(VoucherRedemptionException::class);

    expect(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(1);
});

it('rejects a second voucher while the first one is still standing', function (): void {
    $user = User::factory()->create();

    redeem($user, codeFor(voucherProgram())->code);

    expect(fn (): mixed => redeem($user, codeFor(voucherProgram())->code))
        ->toThrow(VoucherRedemptionException::class);

    expect(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(1);
});

it('rejects a second voucher while the first one is booked', function (): void {
    $user = User::factory()->create();

    redeem($user, codeFor(voucherProgram())->code);
    $user->credits()->update(['status' => UserCreditStatusEnum::InUse]);

    expect(fn (): mixed => redeem($user, codeFor(voucherProgram())->code))
        ->toThrow(VoucherRedemptionException::class);
});

it('lets the person redeem again once the consultancy happened, even inside the access grace', function (): void {
    config()->set('vouchers.access_grace_days', 10);
    $user = User::factory()->create();

    redeem($user, codeFor(voucherProgram())->code);
    $user->credits()->update(['status' => UserCreditStatusEnum::Used, 'used_at' => now()]);

    expect($user->fresh()->hasVoucherAccess())->toBeTrue()
        ->and($user->fresh()->holdsLiveVoucher())->toBeFalse();

    redeem($user, codeFor(voucherProgram())->code);

    expect(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(2);
});

it('lets the person redeem again once the previous voucher expired', function (): void {
    $user = User::factory()->create();

    redeem($user, codeFor(voucherProgram())->code);
    $user->credits()->update(['expires_at' => now()->subDay()]);

    redeem($user, codeFor(voucherProgram())->code);

    expect(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(2);
});

it('leaves nothing behind when the redemption fails', function (): void {
    $code = codeFor(voucherProgram(), batch: ['expires_at' => now()->subDay()]);
    $user = User::factory()->create();

    try {
        redeem($user, $code->code);
    } catch (VoucherRedemptionException) {
        // esperado
    }

    expect(UserCredit::query()->where('holder_id', $user->getKey())->exists())->toBeFalse()
        ->and($code->fresh()->redemptions_count)->toBe(0);
});
