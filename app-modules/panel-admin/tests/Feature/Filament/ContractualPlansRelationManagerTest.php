<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Livewire\Features\SupportTesting\Testable;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\ContractualPlansRelationManager;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();

    $this->company = Company::factory()->create();

    $this->plan = Plan::factory()->create([
        'provider' => BillingProviderEnum::Contractual,
        'type' => BillableTypeEnum::Company,
        'active' => true,
    ]);
});

/** O relation manager renderizado dentro da edição da empresa. */
function contractsManager(Company $company): Testable
{
    return livewire(ContractualPlansRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => EditCompany::class,
    ]);
}

/** Contrato vigente da empresa, com ou sem valor. */
function contractFor(Company $company, Plan $plan, ?int $valueCents): CompanyPlan
{
    return CompanyPlan::factory()->create([
        'company_id' => $company->getKey(),
        'plan_id' => $plan->getKey(),
        'seats' => 20,
        'monthly_value_cents' => $valueCents,
        'status' => CompanyPlanStatusEnum::Active,
        'starts_at' => CarbonImmutable::create(2026, 1, 1),
        'ends_at' => null,
    ]);
}

it('renderiza a lista de contratos', function (): void {
    contractFor($this->company, $this->plan, 350000);

    contractsManager($this->company)->assertOk();
});

describe('valor mensal do contrato', function (): void {
    it('mostra o valor em reais, com vírgula decimal', function (): void {
        // Sem ponto de milhar: ele é da máscara e vive só na tela.
        $contract = contractFor($this->company, $this->plan, 350000);

        contractsManager($this->company)
            ->mountAction(TestAction::make('edit')->table($contract))
            ->assertActionDataSet(['monthly_value_cents' => '3500,00']);
    });

    it('grava em centavos o que foi digitado em reais', function (): void {
        $contract = contractFor($this->company, $this->plan, null);

        contractsManager($this->company)
            ->callAction(TestAction::make('edit')->table($contract), ['monthly_value_cents' => '3500,00'])
            ->assertHasNoActionErrors();

        expect($contract->refresh()->monthly_value_cents)->toBe(350000);
    });

    it('aceita valor com milhar, que chega sem o ponto', function (): void {
        $contract = contractFor($this->company, $this->plan, null);

        contractsManager($this->company)
            ->callAction(TestAction::make('edit')->table($contract), ['monthly_value_cents' => '12500,90'])
            ->assertHasNoActionErrors();

        expect($contract->refresh()->monthly_value_cents)->toBe(1250090);
    });

    it('deixa em branco, que é o contrato ainda não precificado', function (): void {
        $contract = contractFor($this->company, $this->plan, 350000);

        contractsManager($this->company)
            ->callAction(TestAction::make('edit')->table($contract), ['monthly_value_cents' => null])
            ->assertHasNoActionErrors();

        expect($contract->refresh()->monthly_value_cents)->toBeNull();
    });

    it('recusa valor negativo', function (): void {
        $contract = contractFor($this->company, $this->plan, null);

        contractsManager($this->company)
            ->callAction(TestAction::make('edit')->table($contract), ['monthly_value_cents' => '-50,00'])
            ->assertHasActionErrors(['monthly_value_cents']);
    });

    it('recusa texto no lugar do valor', function (): void {
        $contract = contractFor($this->company, $this->plan, null);

        contractsManager($this->company)
            ->callAction(TestAction::make('edit')->table($contract), ['monthly_value_cents' => 'três mil'])
            ->assertHasActionErrors(['monthly_value_cents']);
    });

    it('avisa na tabela o contrato sem valor, em vez de exibir zero', function (): void {
        contractFor($this->company, $this->plan, null);

        contractsManager($this->company)
            ->assertOk()
            ->assertSeeText('Não cadastrado');
    });
});
