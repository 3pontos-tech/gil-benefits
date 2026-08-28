<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Clusters\Financial\FinancialCluster;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\CompaniesAndContracts;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\CompanyStatusWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Cache::flush();
});

/** Empresa com assinatura no status pedido. */
function companyInStatus(string $status, string $name): Company
{
    $company = Company::factory()->create(['name' => $name]);

    $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => $status,
        'quantity' => 10,
    ]);

    return $company;
}

describe('acesso', function (): void {
    it('abre para o perfil financeiro', function (): void {
        actingAsFinancial();

        livewire(CompaniesAndContracts::class)->assertOk();
    });

    it('abre para o customer success', function (): void {
        actingAsCustomerSuccess();

        livewire(CompaniesAndContracts::class)->assertOk();
    });

    it('fecha para Admin comum, mesmo com a URL na mão', function (): void {
        actingAsAdmin();

        expect(CompaniesAndContracts::canAccess())->toBeFalse();
    });

    it('delega o gate ao cluster, sem regra própria', function (): void {
        actingAsFinancial();

        expect(CompaniesAndContracts::canAccess())
            ->toBe(FinancialCluster::canAccess());
    });
});

describe('cards de status', function (): void {
    beforeEach(function (): void {
        actingAsFinancial();
    });

    it('renderiza a página com o widget', function (): void {
        companyInStatus('active', 'Ativa SA');

        livewire(CompaniesAndContracts::class)
            ->assertOk()
            ->assertSeeText('Empresas e Contratos');
    });

    it('mostra a contagem de cada status', function (): void {
        companyInStatus('active', 'Ativa Um');
        companyInStatus('active', 'Ativa Dois');
        companyInStatus('defaulter', 'Atrasada');

        livewire(CompanyStatusWidget::class)
            ->assertOk()
            ->assertSeeText(CompanyFinancialStatusEnum::Active->getLabel())
            ->assertSeeText(CompanyFinancialStatusEnum::Delinquent->getLabel());
    });

    it('exibe o card Em Trial mesmo zerado, com aviso', function (): void {
        companyInStatus('active', 'Ativa SA');

        livewire(CompanyStatusWidget::class)
            ->assertOk()
            ->assertSeeText(CompanyFinancialStatusEnum::Trial->getLabel())
            ->assertSeeText('Sem fluxo de trial B2B hoje');
    });

    it('rotula a data de renovação como estimada', function (): void {
        companyInStatus('active', 'Ativa SA');

        livewire(CompanyStatusWidget::class)
            ->assertOk()
            ->assertSeeText('Data estimada pelo ciclo');
    });
});

describe('navegação por status', function (): void {
    beforeEach(function (): void {
        actingAsFinancial();
    });

    it('lê o status escolhido da URL', function (): void {
        Livewire::withQueryParams(['status' => CompanyFinancialStatusEnum::Delinquent->value])
            ->test(CompaniesAndContracts::class)
            ->assertOk()
            ->assertSet('statusFilter', CompanyFinancialStatusEnum::Delinquent->value);
    });

    it('resolve o status da URL para o enum', function (): void {
        $page = Livewire::withQueryParams(['status' => CompanyFinancialStatusEnum::Active->value])
            ->test(CompaniesAndContracts::class);

        expect($page->instance()->activeStatus())->toBe(CompanyFinancialStatusEnum::Active);
    });

    it('ignora status inválido em vez de quebrar', function (): void {
        $page = Livewire::withQueryParams(['status' => 'inventado'])->test(CompaniesAndContracts::class);

        $page->assertOk();
        expect($page->instance()->activeStatus())->toBeNull();
    });

    it('nasce sem filtro de status', function (): void {
        $page = livewire(CompaniesAndContracts::class);

        $page->assertOk();
        expect($page->instance()->activeStatus())->toBeNull();
    });
});
