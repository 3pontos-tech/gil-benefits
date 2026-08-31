<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\ExportFinancialCsv;
use TresPontosTech\PanelAdmin\Actions\Financial\GetContractsTable;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\CompaniesAndContracts;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ContractsTableWidget;

beforeEach(function (): void {
    Cache::flush();
    $this->now = CarbonImmutable::create(2026, 8, 27, 12, 0, 0);
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

function listedCompany(string $name, string $status, ?CarbonImmutable $createdAt = null): Company
{
    $company = Company::factory()->create(['name' => $name]);

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

/** @return array<int, array<string, mixed>> */
function widgetRows(?string $statusFilter = null, ?int $renewing = null, ?string $search = null): array
{
    $widget = new ContractsTableWidget;
    $widget->pageFilters = null;
    $widget->statusFilter = $statusFilter;
    $widget->renewingWithinDays = $renewing;

    return $widget->visibleRows($search);
}

describe('montagem das linhas', function (): void {
    it('traz empresa, plano, valor, próxima cobrança e situação', function (): void {
        listedCompany('Alpha SA', 'active', CarbonImmutable::create(2026, 3, 10));

        $row = resolve(GetContractsTable::class)->handle($this->filters, $this->now)->first();

        expect($row)->toBeInstanceOf(ContractRow::class)
            ->and($row->companyName)->toBe('Alpha SA')
            ->and($row->monthlyValue->cents)->toBe(44900)
            ->and($row->nextChargeAt?->toDateString())->toBe('2026-09-10')
            ->and($row->status)->toBe(CompanyFinancialStatusEnum::Active);
    });

    it('marca valor desconhecido em vez de zero para contrato sem preço', function (): void {
        $company = Company::factory()->create(['name' => 'Contrato SA']);
        CompanyPlan::factory()->create([
            'company_id' => $company->getKey(),
            'seats' => 20,
            'status' => CompanyPlanStatusEnum::Active,
            'starts_at' => $this->now->subMonths(2),
            'ends_at' => null,
        ]);

        $row = resolve(GetContractsTable::class)->handle($this->filters, $this->now)->first();

        expect($row->monthlyValue->isKnown())->toBeFalse()
            ->and($row->monthlyValue->cents)->toBeNull()
            ->and($row->status)->toBe(CompanyFinancialStatusEnum::Active);
    });

    it('exclui a empresa-balde dos avulsos', function (): void {
        Company::factory()->create(['slug' => Company::DEFAULT_SLUG, 'name' => 'Flamma Bucket']);
        listedCompany('Alpha SA', 'active');

        $names = resolve(GetContractsTable::class)->handle($this->filters, $this->now)
            ->pluck('companyName')->all();

        expect($names)->toBe(['Alpha SA']);
    });

    it('não dispara uma consulta por empresa', function (): void {
        foreach (range(1, 8) as $i) {
            listedCompany('Empresa ' . $i, 'active');
        }

        Cache::flush();
        DB::enableQueryLog();
        resolve(GetContractsTable::class)->handle($this->filters, $this->now);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($queries)->toBeLessThan(8);
    });
});

describe('ordenação e busca', function (): void {
    it('ordena pela cobrança mais próxima por padrão', function (): void {
        listedCompany('Depois', 'active', CarbonImmutable::create(2026, 3, 25));
        listedCompany('Antes', 'active', CarbonImmutable::create(2026, 3, 28));

        $rows = widgetRows();

        expect($rows[0]['company_name'])->toBe('Depois')
            ->and($rows[1]['company_name'])->toBe('Antes');
    });

    it('joga quem não tem cobrança para o fim', function (): void {
        listedCompany('Cancelada', 'canceled', CarbonImmutable::create(2026, 3, 10));
        listedCompany('Ativa', 'active', CarbonImmutable::create(2026, 3, 20));

        $rows = widgetRows();

        expect($rows[0]['company_name'])->toBe('Ativa')
            ->and($rows[1]['company_name'])->toBe('Cancelada');
    });

    it('busca por nome sem diferenciar maiúsculas', function (): void {
        listedCompany('Alpha SA', 'active');
        listedCompany('Beta Ltda', 'active');

        $rows = widgetRows(search: 'alpha');

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['company_name'])->toBe('Alpha SA');
    });
});

describe('filtro vindo do card', function (): void {
    it('filtra por situação', function (): void {
        listedCompany('Ativa', 'active');
        listedCompany('Atrasada', 'defaulter');

        $rows = widgetRows(CompanyFinancialStatusEnum::Delinquent->value);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['company_name'])->toBe('Atrasada');
    });

    it('filtra pela janela de renovação', function (): void {
        listedCompany('Renova logo', 'active', now()->toImmutable()->subMonths(3)->addDays(3));
        listedCompany('Renova longe', 'active', now()->toImmutable()->subMonths(3)->addDays(25));

        $rows = widgetRows(renewing: 7);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['company_name'])->toBe('Renova logo');
    });

    it('a página entrega o filtro da URL ao widget', function (): void {
        $page = Livewire::withQueryParams(['status' => CompanyFinancialStatusEnum::Delinquent->value])
            ->test(CompaniesAndContracts::class);

        expect($page->instance()->getWidgetData())
            ->toHaveKey('statusFilter', CompanyFinancialStatusEnum::Delinquent->value);
    });
});

describe('exportação CSV', function (): void {
    it('exporta exatamente as linhas visíveis, com o mês de referência', function (): void {
        listedCompany('Alpha SA', 'active', CarbonImmutable::create(2026, 3, 10));
        listedCompany('Beta Ltda', 'defaulter');

        $rows = widgetRows(CompanyFinancialStatusEnum::Active->value);
        $response = resolve(ExportFinancialCsv::class)->handle($this->filters, $rows);

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        expect($csv)->toContain('Alpha SA')
            ->and($csv)->not->toContain('Beta Ltda')
            ->and($csv)->toContain('Situação');
    });

    it('neutraliza nome que a planilha leria como fórmula', function (): void {
        listedCompany('=CMD()|malicioso', 'active');

        $response = resolve(ExportFinancialCsv::class)->handle($this->filters, widgetRows());

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        expect($csv)->toContain("'=CMD()|malicioso");
    });

    it('escreve "valor não cadastrado" em vez de vazio', function (): void {
        $company = Company::factory()->create(['name' => 'Contrato SA']);
        CompanyPlan::factory()->create([
            'company_id' => $company->getKey(),
            'seats' => 20,
            'status' => CompanyPlanStatusEnum::Active,
            'starts_at' => now()->subMonths(2),
            'ends_at' => null,
        ]);

        $response = resolve(ExportFinancialCsv::class)->handle($this->filters, widgetRows());

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        expect($csv)->toContain('Valor não cadastrado');
    });
});

describe('tela', function (): void {
    it('monta a tabela dentro da página', function (): void {
        listedCompany('Alpha SA', 'active');

        Livewire::test(CompaniesAndContracts::class)
            ->assertOk()
            ->assertSeeLivewire(ContractsTableWidget::class);
    });

    it('lista a empresa no widget de tabela', function (): void {
        listedCompany('Alpha SA', 'active');

        Livewire::test(ContractsTableWidget::class)
            ->assertOk()
            ->assertSeeText('Alpha SA');
    });
});
