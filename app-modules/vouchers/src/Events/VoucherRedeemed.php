<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Vouchers\Models\VoucherRedemption;

class VoucherRedeemed
{
    use Dispatchable;

    public function __construct(
        public readonly VoucherRedemption $redemption,
    ) {}
}
