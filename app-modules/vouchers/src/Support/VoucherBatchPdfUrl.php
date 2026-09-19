<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Support;

use TresPontosTech\Vouchers\Models\VoucherBatch;

final class VoucherBatchPdfUrl
{
    public const ROUTE = 'voucher-batches.pdf';

    public static function for(VoucherBatch $batch): string
    {
        return route(self::ROUTE, ['batch' => $batch->getKey()]);
    }
}
