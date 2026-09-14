<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Actions\IssueCredits;
use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Vouchers\Events\VoucherRedeemed;
use TresPontosTech\Vouchers\Exceptions\VoucherRedemptionException;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Models\VoucherRedemption;
use TresPontosTech\Vouchers\Support\VoucherCodeGenerator;

/**
 * Quem resgata não entra na empresa parceira: se cadastra sozinho, fica no tenant
 * padrão e é lá que o crédito nasce. O vínculo com a campanha sobrevive inteiro em
 * `user_credits.voucher_redemption_id`, que é por onde a parceira enxerga o resgate.
 */
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
            $this->assertRedeemable($voucherCode, $user);

            $redemption = VoucherRedemption::query()->create([
                'voucher_code_id' => $voucherCode->getKey(),
                'user_id' => $user->getKey(),
                'redeemed_at' => now(),
            ]);

            $voucherCode->increment('redemptions_count');

            $this->issueCredits->handle(new CreditDTO(
                holderId: $user->getKey(),
                ownerId: $user->getKey(),
                companyId: $this->defaultCompanyId(),
                quantity: 1,
                voucherRedemptionId: $redemption->getKey(),
                expiresAt: $this->validUntil($voucherCode, $plan),
            ));

            return $redemption;
        });

        event(new VoucherRedeemed($redemption));

        return $redemption;
    }

    /**
     * @throws VoucherRedemptionException
     */
    public function ensureRedeemable(string $code, ?User $user): void
    {
        $voucherCode = $this->findCode($code);

        $this->findProgram($voucherCode, lock: false);
        $this->assertRedeemable($voucherCode, $user);
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
            ->when($lock, fn (Builder $query): Builder => $query->lockForUpdate())
            ->first();

        if (! $plan instanceof CompanyPlan || $plan->kind !== CompanyPlanKindEnum::CreditsOnly) {
            throw VoucherRedemptionException::programInactive();
        }

        return $plan;
    }

    /**
     * Sem usuário ainda (validação no formulário de cadastro) só dá para conferir o
     * código; as regras de pessoa entram quando ela existe.
     */
    private function assertRedeemable(VoucherCode $code, ?User $user): void
    {
        if ($code->batch->hasExpired()) {
            throw VoucherRedemptionException::batchExpired();
        }

        if ($code->isExhausted()) {
            throw VoucherRedemptionException::codeExhausted();
        }

        if (! $user instanceof User) {
            return;
        }

        if ($this->hasRedeemedBatch($user, $code->voucher_batch_id)) {
            throw VoucherRedemptionException::alreadyRedeemed();
        }

        if ($user->hasActiveVoucherCredit()) {
            throw VoucherRedemptionException::alreadyHoldsVoucher();
        }
    }

    /**
     * O prazo é do crédito, não do contrato: nasce no resgate e vale até o fim da
     * campanha, ou até a data limite do lote quando o contrato é aberto.
     */
    private function validUntil(VoucherCode $code, CompanyPlan $plan): ?CarbonInterface
    {
        $limits = collect([$plan->ends_at?->endOfDay(), $code->batch->expires_at])->filter();

        return $limits->min();
    }

    private function defaultCompanyId(): string
    {
        return (string) Company::default()->getKey();
    }

    private function hasRedeemedBatch(User $user, string $batchId): bool
    {
        return VoucherRedemption::query()
            ->where('user_id', $user->getKey())
            ->whereHas('code', fn (Builder $query): Builder => $query->where('voucher_batch_id', $batchId))
            ->exists();
    }
}
