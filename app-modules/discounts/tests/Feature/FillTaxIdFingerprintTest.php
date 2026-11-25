<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Discounts\Actions\ReserveDiscountGrant;
use TresPontosTech\Discounts\Enums\DiscountIneligibilityReasonEnum;
use TresPontosTech\Discounts\Models\Discount;
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

it('preenche a impressão de cadastros inseridos em lote, sem o observer, para a conferência de CPF', function (): void {
    config(['discounts.tax_id_fingerprint_key' => 'clau-flamma-test-tax-key']);
    $discount = Discount::factory()->active()->create();

    Detail::query()->insert([
        'user_id' => User::factory()->create()->getKey(),
        'company_id' => Company::factory()->create()->getKey(),
        'document_id' => '12.345.678-9',
        'tax_id' => '529.982.247-25',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('discounts:fingerprint-tax-ids')->assertSuccessful();

    $outcome = resolve(ReserveDiscountGrant::class)->handle(
        discount: $discount,
        source: 'clau',
        externalRef: 'clau-importado',
        name: 'Ana Souza',
        email: 'ana.nova@example.com',
        taxIdFingerprint: TaxIdFingerprint::make('52998224725'),
        consentTermVersion: '1',
        consentAcceptedAt: now(),
    );

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::ExistingAccount);
});
