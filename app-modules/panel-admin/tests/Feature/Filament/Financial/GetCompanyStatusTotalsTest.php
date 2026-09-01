<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\GetCompanyStatusTotals;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

beforeEach(function (): void {
    Cache::flush();
    $this->action = resolve(GetCompanyStatusTotals::class);
    $this->filters = FinancialFilters::fromPageFilters(null);
    $this->now = CarbonImmutable::create(2026, 8, 27, 12, 0, 0);
});

function subscribedCompany(string $status, ?CarbonImmutable $createdAt = null, ?string $name = null): Company
{
    $company = Company::factory()->create($name === null ? [] : ['name' => $name]);

    $subscription = $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => $status,
        'quantity' => 10,
    ]);

    if ($createdAt instanceof CarbonImmutable) {
        $subscription->forceFill(['created_at' => $createdAt])->save();
    }

    return $company;
}

it('conta cada empresa no seu status', function (): void {
    subscribedCompany('active');
    subscribedCompany('active');
    subscribedCompany('defaulter');
    subscribedCompany('canceled');

    $totals = $this->action->handle($this->filters, $this->now);

    expect($totals->count(CompanyFinancialStatusEnum::Active))->toBe(2)
        ->and($totals->count(CompanyFinancialStatusEnum::Delinquent))->toBe(1)
        ->and($totals->count(CompanyFinancialStatusEnum::Cancelled))->toBe(1);
});

it('devolve zero explícito para status sem nenhuma empresa', function (): void {
    subscribedCompany('active');

    $totals = $this->action->handle($this->filters, $this->now);

    expect($totals->byStatus)->toHaveKey(CompanyFinancialStatusEnum::Trial->value)
        ->and($totals->count(CompanyFinancialStatusEnum::Trial))->toBe(0);
});

it('exclui a empresa-balde dos avulsos', function (): void {
    Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);
    subscribedCompany('active');

    $totals = $this->action->handle($this->filters, $this->now);

    expect($totals->count(CompanyFinancialStatusEnum::Active))->toBe(1)
        ->and($totals->count(CompanyFinancialStatusEnum::None))->toBe(0);
});

it('conta empresa sem assinatura e sem contrato como sem plano', function (): void {
    Company::factory()->create();

    expect($this->action->handle($this->filters, $this->now)->count(CompanyFinancialStatusEnum::None))->toBe(1);
});

it('conta contrato ativo como empresa ativa', function (): void {
    $company = Company::factory()->create();
    CompanyPlan::factory()->create([
        'company_id' => $company->getKey(),
        'seats' => 10,
        'status' => CompanyPlanStatusEnum::Active,
        'starts_at' => $this->now->subMonths(2),
        'ends_at' => null,
    ]);

    expect($this->action->handle($this->filters, $this->now)->count(CompanyFinancialStatusEnum::Active))->toBe(1);
});

it('soma a base viva sem contar canceladas', function (): void {
    subscribedCompany('active');
    subscribedCompany('defaulter');
    subscribedCompany('canceled');

    expect($this->action->handle($this->filters, $this->now)->living())->toBe(2);
});

describe('renovações próximas', function (): void {
    it('conta as janelas de 30 e 7 dias', function (): void {
        subscribedCompany('active', CarbonImmutable::create(2026, 3, 30));
        subscribedCompany('active', CarbonImmutable::create(2026, 3, 20));

        $totals = $this->action->handle($this->filters, $this->now);

        expect($totals->renewingIn30Days)->toBe(2)
            ->and($totals->renewingIn7Days)->toBe(1);
    });

    it('não conta renovação de empresa cancelada', function (): void {
        subscribedCompany('canceled', CarbonImmutable::create(2026, 3, 30));

        expect($this->action->handle($this->filters, $this->now)->renewingIn30Days)->toBe(0);
    });
});

describe('filtro e cache', function (): void {
    it('respeita o filtro por empresa', function (): void {
        $alvo = subscribedCompany('active');
        subscribedCompany('active');

        $filtrado = FinancialFilters::fromPageFilters(['companies' => [$alvo->getKey()]]);

        expect($this->action->handle($filtrado, $this->now)->count(CompanyFinancialStatusEnum::Active))->toBe(1);
    });

    it('serve do cache na segunda chamada', function (): void {
        subscribedCompany('active');
        $this->action->handle($this->filters, $this->now);

        subscribedCompany('active');

        expect($this->action->handle($this->filters, $this->now)->count(CompanyFinancialStatusEnum::Active))->toBe(1);
    });

    it('recalcula depois de descartar o bloco', function (): void {
        subscribedCompany('active');
        $this->action->handle($this->filters, $this->now);

        subscribedCompany('active');
        $this->action->forget($this->filters);

        expect($this->action->handle($this->filters, $this->now)->count(CompanyFinancialStatusEnum::Active))->toBe(2);
    });
});

it('não dispara uma consulta por empresa', function (): void {
    foreach (range(1, 8) as $i) {
        subscribedCompany('active');
    }

    Cache::flush();
    DB::enableQueryLog();
    $this->action->handle($this->filters, $this->now);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Empresas + assinaturas pré-carregadas: o número não pode crescer com a base.
    expect($queries)->toBeLessThan(8);
});
