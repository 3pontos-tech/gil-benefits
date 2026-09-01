<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Support\CompanyStatusResolver;
use TresPontosTech\Company\Models\Company;

beforeEach(function (): void {
    $this->resolver = new CompanyStatusResolver;
    $this->now = CarbonImmutable::create(2026, 8, 27, 12, 0, 0);
});

/**
 * Empresa com uma assinatura no status pedido, ancorada na data informada.
 */
function companyWithStatus(string $status, ?CarbonImmutable $createdAt = null, ?CarbonImmutable $trialEndsAt = null): Company
{
    $company = Company::factory()->create();

    $subscription = $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => $status,
        'quantity' => 10,
        'trial_ends_at' => $trialEndsAt,
    ]);

    if ($createdAt instanceof CarbonImmutable) {
        $subscription->forceFill(['created_at' => $createdAt])->save();
    }

    return $company->fresh();
}

describe('status unificado', function (): void {
    it('traduz os status dos dois vocabulários', function (string $stripeStatus, CompanyFinancialStatusEnum $expected): void {
        $status = $this->resolver->resolve(companyWithStatus($stripeStatus), $this->now)->status;

        expect($status)->toBe($expected);
    })->with([
        'cashier ativo' => ['active', CompanyFinancialStatusEnum::Active],
        'cashier em trial' => ['trialing', CompanyFinancialStatusEnum::Trial],
        'cashier atrasado' => ['past_due', CompanyFinancialStatusEnum::Delinquent],
        'cashier cancelado' => ['canceled', CompanyFinancialStatusEnum::Cancelled],
        'gateway pendente' => ['pending', CompanyFinancialStatusEnum::Trial],
        'gateway inadimplente' => ['defaulter', CompanyFinancialStatusEnum::Delinquent],
        'gateway inativo' => ['inactive', CompanyFinancialStatusEnum::Cancelled],
    ]);

    it('trata status desconhecido como cancelado, sem inflar a base', function (): void {
        $status = $this->resolver->resolve(companyWithStatus('algo_que_ninguem_mapeou'), $this->now)->status;

        expect($status)->toBe(CompanyFinancialStatusEnum::Cancelled);
    });

    it('considera trial quando trial_ends_at está no futuro, mesmo com status ativo', function (): void {
        $company = companyWithStatus('active', trialEndsAt: $this->now->addDays(5));

        expect($this->resolver->resolve($company, $this->now)->status)->toBe(CompanyFinancialStatusEnum::Trial);
    });

    it('volta a ser ativa quando o trial já venceu', function (): void {
        $company = companyWithStatus('active', trialEndsAt: $this->now->subDay());

        expect($this->resolver->resolve($company, $this->now)->status)->toBe(CompanyFinancialStatusEnum::Active);
    });

    it('não ressuscita assinatura cancelada por causa do trial', function (): void {
        $company = companyWithStatus('canceled', trialEndsAt: $this->now->addDays(5));

        expect($this->resolver->resolve($company, $this->now)->status)->toBe(CompanyFinancialStatusEnum::Cancelled);
    });

    it('usa o contrato quando não há assinatura', function (): void {
        $company = Company::factory()->create();
        CompanyPlan::factory()->create([
            'company_id' => $company->getKey(),
            'seats' => 10,
            'status' => CompanyPlanStatusEnum::Active,
            'starts_at' => $this->now->subMonths(3),
            'ends_at' => null,
        ]);

        expect($this->resolver->resolve($company->fresh(), $this->now)->status)
            ->toBe(CompanyFinancialStatusEnum::Active);
    });

    it('devolve "sem plano" quando não há nem assinatura nem contrato', function (): void {
        $result = $this->resolver->resolve(Company::factory()->create(), $this->now);

        expect($result->status)->toBe(CompanyFinancialStatusEnum::None)
            ->and($result->nextChargeAt)->toBeNull();
    });
});

describe('próxima cobrança estimada', function (): void {
    it('projeta o mesmo dia do mês seguinte', function (): void {
        $company = companyWithStatus('active', CarbonImmutable::create(2026, 3, 10));

        $next = $this->resolver->resolve($company, $this->now)->nextChargeAt;

        expect($next?->toDateString())->toBe('2026-09-10');
    });

    it('projeta o próximo dia ainda futuro dentro do mês corrente', function (): void {
        $company = companyWithStatus('active', CarbonImmutable::create(2026, 3, 30));

        expect($this->resolver->resolve($company, $this->now)->nextChargeAt?->toDateString())
            ->toBe('2026-08-30');
    });

    it('encolhe a âncora do dia 31 em mês curto, sem pular para o mês seguinte', function (): void {
        $company = companyWithStatus('active', CarbonImmutable::create(2026, 1, 31));
        $novembro = CarbonImmutable::create(2026, 11, 5, 12, 0, 0);

        expect($this->resolver->resolve($company, $novembro)->nextChargeAt?->toDateString())
            ->toBe('2026-11-30');
    });

    it('não projeta cobrança para empresa cancelada', function (): void {
        $company = companyWithStatus('canceled', CarbonImmutable::create(2026, 3, 10));

        expect($this->resolver->resolve($company, $this->now)->nextChargeAt)->toBeNull();
    });

    it('projeta cobrança para inadimplente, que ainda tem ciclo', function (): void {
        $company = companyWithStatus('defaulter', CarbonImmutable::create(2026, 3, 10));

        expect($this->resolver->resolve($company, $this->now)->nextChargeAt?->toDateString())
            ->toBe('2026-09-10');
    });

    it('ancora o contrato em starts_at', function (): void {
        $company = Company::factory()->create();
        CompanyPlan::factory()->create([
            'company_id' => $company->getKey(),
            'seats' => 10,
            'status' => CompanyPlanStatusEnum::Active,
            'starts_at' => CarbonImmutable::create(2026, 2, 5),
            'ends_at' => null,
        ]);

        expect($this->resolver->resolve($company->fresh(), $this->now)->nextChargeAt?->toDateString())
            ->toBe('2026-09-05');
    });
});

describe('janela de renovação', function (): void {
    it('reconhece renovação dentro da janela', function (): void {
        $company = companyWithStatus('active', CarbonImmutable::create(2026, 3, 30));
        $result = $this->resolver->resolve($company, $this->now);

        expect($result->renewsWithin(7, $this->now))->toBeTrue()
            ->and($result->renewsWithin(1, $this->now))->toBeFalse();
    });

    it('nunca reconhece renovação sem data de cobrança', function (): void {
        $company = companyWithStatus('canceled', CarbonImmutable::create(2026, 3, 30));

        expect($this->resolver->resolve($company, $this->now)->renewsWithin(30, $this->now))->toBeFalse();
    });
});
