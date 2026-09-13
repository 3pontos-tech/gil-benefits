<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Support;

use TresPontosTech\Vouchers\Models\VoucherCode;

final class VoucherRedemptionUrl
{
    public const ROUTE = 'filament.app.pages.redeem-voucher';

    public const QUERY_PARAMETER = 'voucher';

    public static function for(VoucherCode $code): string
    {
        $code->loadMissing('batch.company');

        return route(self::ROUTE, [
            'tenant' => $code->batch->company?->slug,
            self::QUERY_PARAMETER => $code->code,
        ]);
    }
}
