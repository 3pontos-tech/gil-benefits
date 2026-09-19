<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Support;

use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

/**
 * PDF e QR ficam em disco privado; estas rotas os servem para a parceira, fora do escopo de
 * tenant do Filament. O prefixo é próprio para não disputar com `company/{tenant}/...`.
 */
final class PartnerVoucherUrls
{
    public const PDF_ROUTE = 'partner.voucher-batches.pdf';

    public const QR_ROUTE = 'partner.voucher-codes.qr';

    public static function pdf(VoucherBatch $batch): string
    {
        return route(self::PDF_ROUTE, ['batch' => $batch->getKey()]);
    }

    public static function qr(VoucherCode $code): string
    {
        return route(self::QR_ROUTE, ['code' => $code->getKey()]);
    }
}
