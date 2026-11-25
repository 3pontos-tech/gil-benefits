<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use TresPontosTech\Discounts\Support\TaxIdFingerprint;

const FILL_TAX_ID_FINGERPRINT_VECTOR = 'v1:3ba82e6b122bd66791c11173d5117031085d20215b1a6aaae4a37eff1cb8d9ad';

it('grava a impressão do CPF ao salvar o cadastro com a chave configurada', function (): void {
    config(['discounts.tax_id_fingerprint_key' => 'clau-flamma-test-tax-key']);

    $detail = Detail::factory()->create(['tax_id' => '52998224725']);

    expect($detail->fresh()->tax_id_fingerprint)->toBe(FILL_TAX_ID_FINGERPRINT_VECTOR);
});

it('deixa a impressão nula sem a chave configurada', function (): void {
    config(['discounts.tax_id_fingerprint_key' => null]);

    $detail = Detail::factory()->create(['tax_id' => '52998224725']);

    expect($detail->fresh()->tax_id_fingerprint)->toBeNull();
});

it('preenche a impressão dos cadastros antigos, inclusive os apagados', function (): void {
    config(['discounts.tax_id_fingerprint_key' => null]);

    $active = Detail::factory()->create(['tax_id' => '52998224725']);
    $trashed = Detail::factory()->create(['tax_id' => '11144477735']);
    $trashed->delete();

    config(['discounts.tax_id_fingerprint_key' => 'clau-flamma-test-tax-key']);

    $this->artisan('discounts:fingerprint-tax-ids')->assertSuccessful();

    expect(Detail::query()->findOrFail($active->getKey())->tax_id_fingerprint)->toBe(FILL_TAX_ID_FINGERPRINT_VECTOR)
        ->and(Detail::withTrashed()->findOrFail($trashed->getKey())->tax_id_fingerprint)->toBe(TaxIdFingerprint::make('11144477735'));
});

it('falha ao preencher as impressões sem a chave configurada', function (): void {
    config(['discounts.tax_id_fingerprint_key' => null]);

    $this->artisan('discounts:fingerprint-tax-ids')->assertFailed();
});
