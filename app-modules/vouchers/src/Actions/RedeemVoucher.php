<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Credits\Actions\IssueCredits;
use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Vouchers\Events\VoucherRedeemed;
use TresPontosTech\Vouchers\Exceptions\VoucherRedemptionException;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Models\VoucherRedemption;
use TresPontosTech\Vouchers\Support\VoucherCodeGenerator;

final readonly class RedeemVoucher
{
    public function __construct(
        private IssueCredits $issueCredits,
    ) {}

    public function handle(User $user, string $code): VoucherRedemption
    {
        $redemption = DB::transaction(function () use ($user, $code): VoucherRedemption {
            $voucherCode = $this->findCode($code);
            $plan = $this->findProgram($voucherCode, lock: true);

            $voucherCode->refresh();
            $this->assertRedeemable($voucherCode, $plan, $user);

            $redemption = VoucherRedemption::query()->create([
                'voucher_code_id' => $voucherCode->getKey(),
                'user_id' => $user->getKey(),
                'redeemed_at' => now(),
            ]);

            $voucherCode->increment('redemptions_count');

            $this->issueCredits->handle(new CreditDTO(
                holderId: $user->getKey(),
                ownerId: $user->getKey(),
                companyId: $plan->company_id,
                quantity: 1,
                voucherRedemptionId: $redemption->getKey(),
            ));

            return $redemption;
        });

        event(new VoucherRedeemed($redemption));

        return $redemption;
    }

    /**
     * @throws VoucherRedemptionException
     */
    public function ensureRedeemable(string $code, User $user): void
    {
        $voucherCode = $this->findCode($code);

        $this->assertRedeemable($voucherCode, $this->findProgram($voucherCode, lock: false), $user);
    }

    private function findCode(string $code): VoucherCode
    {
        $voucherCode = VoucherCode::query()
            ->where('code', VoucherCodeGenerator::normalize($code))
            ->first();

        if (! $voucherCode instanceof VoucherCode) {
            throw VoucherRedemptionException::codeNotFound();
        }

        return $voucherCode;
    }

    private function findProgram(VoucherCode $code, bool $lock): CompanyPlan
    {
        $plan = CompanyPlan::query()
            ->whereKey($code->batch->company_plan_id)
            ->activeOn()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();

        if (! $plan instanceof CompanyPlan || $plan->kind !== CompanyPlanKindEnum::CreditsOnly) {
            throw VoucherRedemptionException::programInactive();
        }

        return $plan;
    }

    private function assertRedeemable(VoucherCode $code, CompanyPlan $plan, User $user): void
    {
        if (! $this->belongsToProgramCompany($user, $plan->company_id)) {
            throw VoucherRedemptionException::notFromUserCompany();
        }

        if ($code->batch->hasExpired()) {
            throw VoucherRedemptionException::batchExpired();
        }

        if ($code->isExhausted()) {
            throw VoucherRedemptionException::codeExhausted();
        }

        if ($this->hasRedeemedBatch($user, $code->voucher_batch_id)) {
            throw VoucherRedemptionException::alreadyRedeemed();
        }
    }

    private function hasRedeemedBatch(User $user, string $batchId): bool
    {
        return VoucherRedemption::query()
            ->where('user_id', $user->getKey())
            ->whereHas('code', fn ($query) => $query->where('voucher_batch_id', $batchId))
            ->exists();
    }

    private function belongsToProgramCompany(User $user, string $companyId): bool
    {
        return $user->companies()
            ->whereKey($companyId)
            ->wherePivot('active', true)
            ->exists();
    }
}
