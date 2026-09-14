<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use Carbon\CarbonInterface;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

/**
 * Queima o crédito de voucher que passou da validade gravada no resgate.
 *
 * A data vive no crédito, não no contrato da parceira: quem resgatou no último dia da
 * campanha leva o mesmo prazo de quem resgatou no primeiro, e encerrar o contrato mais
 * cedo não derruba o que já foi entregue. Só o que está `available` some — o que já
 * virou agendamento fica, pelo mesmo critério do RevokeCreditGrant.
 */
final readonly class ExpireVoucherCredits
{
    public function handle(?CarbonInterface $moment = null): int
    {
        return UserCredit::query()
            ->whereNotNull('voucher_redemption_id')
            ->where('status', UserCreditStatusEnum::Available)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $moment ?? now())
            ->update(['status' => UserCreditStatusEnum::Expired]);
    }
}
