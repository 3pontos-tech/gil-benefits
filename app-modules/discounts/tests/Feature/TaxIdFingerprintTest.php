<?php

declare(strict_types=1);

use TresPontosTech\Discounts\Support\TaxIdFingerprint;

const CLAU_TAX_ID_FINGERPRINT_VECTOR = 'v1:3ba82e6b122bd66791c11173d5117031085d20215b1a6aaae4a37eff1cb8d9ad';

it('gera a impressão do CPF com o vetor fixo, com ou sem máscara', function (): void {
    config(['discounts.tax_id_fingerprint_key' => 'clau-flamma-test-tax-key']);

    expect(TaxIdFingerprint::make('52998224725'))->toBe(CLAU_TAX_ID_FINGERPRINT_VECTOR)
        ->and(TaxIdFingerprint::make('529.982.247-25'))->toBe(CLAU_TAX_ID_FINGERPRINT_VECTOR);
});

it('não gera impressão sem a chave configurada', function (): void {
    config(['discounts.tax_id_fingerprint_key' => null]);

    expect(TaxIdFingerprint::isConfigured())->toBeFalse()
        ->and(fn (): string => TaxIdFingerprint::make('52998224725'))
        ->toThrow(RuntimeException::class, 'CLAU_TAX_ID_KEY não configurada.');
});
