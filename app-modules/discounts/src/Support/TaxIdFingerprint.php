<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Support;

use RuntimeException;

final class TaxIdFingerprint
{
    public static function isConfigured(): bool
    {
        return filled(config('discounts.tax_id_fingerprint_key'));
    }

    public static function make(string $cpf): string
    {
        throw_unless(self::isConfigured(), RuntimeException::class, 'CLAU_TAX_ID_KEY não configurada.');

        $digits = preg_replace('/\D+/', '', $cpf) ?? '';

        return 'v1:' . hash_hmac('sha256', $digits, (string) config('discounts.tax_id_fingerprint_key'));
    }
}
