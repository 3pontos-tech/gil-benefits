<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Support;

use TresPontosTech\Vouchers\Models\VoucherCode;

/**
 * O QR da carteirinha cai no cadastro, não numa página interna: quem recebe o voucher
 * ainda não tem conta. Montada pelo NOME da rota para o módulo não depender do painel.
 */
final class VoucherRedemptionUrl
{
    public const ROUTE = 'filament.app.auth.register';

    public const QUERY_PARAMETER = 'voucher';

    public static function for(VoucherCode $code): string
    {
        return route(self::ROUTE, [self::QUERY_PARAMETER => $code->code]);
    }
}
