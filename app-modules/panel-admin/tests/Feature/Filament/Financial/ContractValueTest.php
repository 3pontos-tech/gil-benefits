<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Enums\MonthlyValueSourceEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Support\RevenueResolver;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\GetContractsTable;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueKpis;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

beforeEach(function (): void {
    Cache::flush();
    $this->now = CarbonImmutable::create(2026, 8, 27, 12, 0, 0);
    $this->filters = FinancialFilters::fromPageFilters(null);
});

/** Empresa com contrato B2B, com ou sem valor preenchido. */
function contractedCompany(string $name, ?int $valueCents, ?CarbonImmutable $startsAt = null): Company
{
    $company = Company::factory()->create(['name' => $name]);

    CompanyPlan::factory()->create([
        'company_id' => $company->getKey(),
        'seats' => 20,
        'monthly_value_cents' => $valueCents,
        'status' => CompanyPlanStatusEnum::Active,
        'starts_at' => $startsAt ?? CarbonImmutable::create(2026, 1, 1),
        'ends_at' => null,
    ]);

    return $company;
}

describe('valor do contrato', function (): void {
    it('vale o que o financeiro preencheu', function (): void {
        $company = contractedCompany('Contratada', 350000);

        $value = resolve(RevenueResolver::class)->monthlyValueForCompany($company);

        expect($value->isKnown())->toBeTrue()
            ->and($value->cents)->toBe(350000)
            ->and($value->source)->toBe(MonthlyValueSourceEnum::ContractualPlan);
    });

    it('continua desconhecida enquanto ninguém preencher, e nunca vale zero', function (): void {
        $company = contractedCompany('Sem valor', null);

        $value = resolve(RevenueResolver::class)->monthlyValueForCompany($company);

        expect($value->isKnown())->toBeFalse()
            ->and($value->cents)->toBeNull();
    });

    it('não estima pela tabela de assento', function (): void {
        // 20 cadeiras cairiam em R$ 34,90 cada se o resolver chutasse.
        $company = contractedCompany('Sem valor', null);

        expect(resolve(RevenueResolver::class)->monthlyValueForCompany($company)->cents)->not->toBe(69800);
    });

    it('deixa a assinatura ter precedência sobre o contrato', function (): void {
        $company = contractedCompany('As duas coisas', 350000);
        $company->subscriptions()->create([
            'type' => 'company',
            'stripe_id' => 'sub_' . fake()->unique()->uuid(),
            'stripe_status' => 'active',
            'quantity' => 10,
        ]);

        $value = resolve(RevenueResolver::class)->monthlyValueForCompany($company->fresh());

        // A assinatura é cobrança real; o contrato é um valor digitado.
        expect($value->cents)->toBe(44900)
            ->and($value->source)->toBe(MonthlyValueSourceEnum::SubscriptionSeatTier);
    });

    it('ignora contrato encerrado', function (): void {
        $company = Company::factory()->create();
        CompanyPlan::factory()->create([
            'company_id' => $company->getKey(),
            'seats' => 20,
            'monthly_value_cents' => 350000,
            'status' => CompanyPlanStatusEnum::Active,
            'starts_at' => CarbonImmutable::create(2026, 1, 1),
            'ends_at' => CarbonImmutable::create(2026, 3, 1),
        ]);

        expect(resolve(RevenueResolver::class)->monthlyValueForCompany($company)->isKnown())->toBeFalse();
    });
});

describe('efeito no cockpit', function (): void {
    it('entra na listagem de contratos com o valor preenchido', function (): void {
        contractedCompany('Contratada', 350000);

        $row = resolve(GetContractsTable::class)->handle($this->filters, $this->now)->first();

        expect($row->monthlyValue->isKnown())->toBeTrue()
            ->and($row->monthlyValue->cents)->toBe(350000);
    });

    it('entra no MRR do mês', function (): void {
        contractedCompany('Contratada', 350000);

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters, $this->now);

        expect($kpis->current->b2bCents)->toBe(350000)
            ->and($kpis->current->payingCompanies)->toBe(1)
            ->and($kpis->current->companiesWithKnownValue)->toBe(1);
    });

    it('conta como pagante sem valor conhecido enquanto está em branco', function (): void {
        contractedCompany('Sem valor', null);

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters, $this->now);

        expect($kpis->current->b2bCents)->toBe(0)
            ->and($kpis->current->payingCompanies)->toBe(1)
            ->and($kpis->current->companiesWithKnownValue)->toBe(0);
    });

    it('não conta a empresa duas vezes quando ela tem contrato e assinatura', function (): void {
        $company = contractedCompany('As duas coisas', 350000);
        $company->subscriptions()->create([
            'type' => 'company',
            'stripe_id' => 'sub_' . fake()->unique()->uuid(),
            'stripe_status' => 'active',
            'quantity' => 10,
            'created_at' => CarbonImmutable::create(2026, 1, 1),
        ]);

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters, $this->now);

        expect($kpis->current->payingCompanies)->toBe(1)
            ->and($kpis->current->b2bCents)->toBe(44900);
    });

    it('entra nos meses anteriores em que o contrato esteve vigente', function (): void {
        contractedCompany('Antiga', 350000, startsAt: CarbonImmutable::create(2026, 6, 1));

        $julho = FinancialFilters::fromPageFilters(['month' => '2026-07']);

        expect(resolve(GetRevenueKpis::class)->handle($julho, $this->now)->current->b2bCents)->toBe(350000);
    });

    it('fica fora dos meses anteriores ao início da vigência', function (): void {
        contractedCompany('Nova', 350000, startsAt: CarbonImmutable::create(2026, 8, 1));

        $julho = FinancialFilters::fromPageFilters(['month' => '2026-07']);

        expect(resolve(GetRevenueKpis::class)->handle($julho, $this->now)->current->b2bCents)->toBe(0);
    });
});
