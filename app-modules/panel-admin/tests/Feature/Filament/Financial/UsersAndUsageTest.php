<?php

declare(strict_types=1);

use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\GetActivationTotals;
use TresPontosTech\PanelAdmin\Actions\Financial\GetCompanyUsage;
use TresPontosTech\PanelAdmin\Actions\Financial\GetCompanyUserBreakdown;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\UsersAndUsage;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ActivationTotalsWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\CompanyUsageTableWidget;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo(CarbonImmutable::create(2026, 8, 27, 12, 0, 0));
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

/**
 * Empresa com contrato, colaboradores e um recorte de quem usou.
 *
 * @param  int  $usedNow  Quantos realizaram consultoria no mês corrente.
 * @param  int  $usedBefore  Quantos usaram só em meses anteriores.
 * @param  int  $unverified  Quantos nunca verificaram o e-mail.
 */
function usageCompany(string $name, int $seats, int $employees, int $usedNow, int $usedBefore = 0, int $unverified = 0): Company
{
    $company = Company::factory()->create(['name' => $name]);

    CompanyPlan::factory()->create([
        'company_id' => $company->getKey(),
        'seats' => $seats,
        'status' => CompanyPlanStatusEnum::Active,
        'starts_at' => now()->subMonths(6),
        'ends_at' => null,
    ]);

    foreach (range(1, $employees) as $index) {
        // Os que não verificaram vêm do fim da lista, para não colidirem com os
        // que usaram — cada cenário exercita um grupo por vez.
        $user = User::factory()->create([
            'email_verified_at' => $index > $employees - $unverified ? null : now()->subMonths(6),
        ]);

        $company->employees()->attach($user->getKey(), [
            'active' => true,
            'created_at' => now()->subMonths(3),
        ]);

        if ($index <= $usedNow) {
            Appointment::factory()->create([
                'company_id' => $company->getKey(),
                'user_id' => $user->getKey(),
                'status' => AppointmentStatus::Completed,
                'appointment_at' => now()->subDays(5),
            ]);

            continue;
        }

        if ($index <= $usedNow + $usedBefore) {
            Appointment::factory()->create([
                'company_id' => $company->getKey(),
                'user_id' => $user->getKey(),
                'status' => AppointmentStatus::Completed,
                'appointment_at' => now()->subMonths(3),
            ]);
        }
    }

    return $company;
}

describe('utilização por empresa', function (): void {
    it('cruza contratados, cadastrados, quem usou e quem nunca usou', function (): void {
        usageCompany('Alpha SA', seats: 20, employees: 10, usedNow: 3, usedBefore: 2);

        $row = resolve(GetCompanyUsage::class)->handle($this->filters)->firstWhere('companyName', 'Alpha SA');

        expect($row->seats)->toBe(20)
            ->and($row->registered)->toBe(10)
            ->and($row->usedInPeriod)->toBe(3)
            ->and($row->neverUsed)->toBe(5);
    });

    it('conta como nunca utilizado quem só usou em outro mês somente se nunca usou de fato', function (): void {
        usageCompany('Beta Ltda', seats: 10, employees: 4, usedNow: 0, usedBefore: 4);

        $row = resolve(GetCompanyUsage::class)->handle($this->filters)->firstWhere('companyName', 'Beta Ltda');

        expect($row->usedInPeriod)->toBe(0)
            ->and($row->neverUsed)->toBe(0);
    });

    it('calcula a fatia que nunca usou sobre os cadastrados', function (): void {
        usageCompany('Gama SA', seats: 10, employees: 10, usedNow: 2);

        $row = resolve(GetCompanyUsage::class)->handle($this->filters)->firstWhere('companyName', 'Gama SA');

        expect($row->neverUsedRate())->toBe(80.0);
    });
});

describe('detalhe por colaborador', function (): void {
    it('classifica cada colaborador pelo uso', function (): void {
        $company = usageCompany('Alpha SA', seats: 10, employees: 4, usedNow: 1, usedBefore: 1, unverified: 0);

        $users = resolve(GetCompanyUserBreakdown::class)->handle(
            (string) $company->getKey(),
            $this->filters->period,
        );

        $labels = $users->pluck('statusLabel')->countBy();

        expect($users)->toHaveCount(4)
            ->and($labels->get('Usou no mês'))->toBe(1)
            ->and($labels->get('Já usou antes'))->toBe(1)
            ->and($labels->get('Nunca usou'))->toBe(2);
    });

    it('marca quem nunca verificou o e-mail como sem acesso', function (): void {
        $company = usageCompany('Alpha SA', seats: 10, employees: 2, usedNow: 0, unverified: 2);

        $users = resolve(GetCompanyUserBreakdown::class)->handle(
            (string) $company->getKey(),
            $this->filters->period,
        );

        expect($users->pluck('statusLabel')->unique()->all())->toBe(['Sem acesso']);
    });
});

describe('ativação agregada', function (): void {
    it('separa ativos, inativos e sem acesso', function (): void {
        usageCompany('Alpha SA', seats: 10, employees: 10, usedNow: 3, unverified: 2);

        ['current' => $totals] = resolve(GetActivationTotals::class)->handle($this->filters);

        expect($totals->total)->toBe(10)
            ->and($totals->active)->toBe(3)
            ->and($totals->withoutAccess)->toBe(2)
            ->and($totals->inactive)->toBe(5);
    });

    it('calcula a taxa de ativação sobre o total', function (): void {
        usageCompany('Alpha SA', seats: 10, employees: 10, usedNow: 4);

        ['current' => $totals] = resolve(GetActivationTotals::class)->handle($this->filters);

        expect($totals->activationRate())->toBe(40.0);
    });

    it('não compara com um mês anterior sem base', function (): void {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $company->employees()->attach($user->getKey(), ['active' => true, 'created_at' => now()]);

        ['previous' => $previous] = resolve(GetActivationTotals::class)->handle($this->filters);

        expect($previous)->toBeNull();
    });

    it('particiona a base: ativo, sem acesso e inativo somam o total', function (): void {
        usageCompany('Alpha SA', seats: 10, employees: 6, usedNow: 2, unverified: 2);

        ['current' => $totals] = resolve(GetActivationTotals::class)->handle($this->filters);

        expect($totals->active + $totals->withoutAccess + $totals->inactive)->toBe($totals->total)
            ->and($totals->active)->toBe(2)
            ->and($totals->withoutAccess)->toBe(2)
            ->and($totals->inactive)->toBe(2);
    });

    it('conta uma vez só quem não verificou o e-mail mas usou o benefício', function (): void {
        // O caso que sobrepunha os grupos: sem verificação e com consultoria no
        // mês. O uso é fato e vence o proxy, então ele é ativo — e some de
        // "sem acesso", em vez de aparecer nos dois e furar o total.
        $company = Company::factory()->create();
        $user = User::factory()->create(['email_verified_at' => null]);
        $company->employees()->attach($user->getKey(), ['active' => true, 'created_at' => now()->subMonth()]);

        Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'status' => AppointmentStatus::Completed,
            'appointment_at' => now()->subDays(2),
        ]);

        ['current' => $totals] = resolve(GetActivationTotals::class)->handle($this->filters);

        expect($totals->total)->toBe(1)
            ->and($totals->active)->toBe(1)
            ->and($totals->withoutAccess)->toBe(0)
            ->and($totals->inactive)->toBe(0);
    });

    it('não conta usuário sem vínculo de empresa', function (): void {
        User::factory()->count(3)->create();

        ['current' => $totals] = resolve(GetActivationTotals::class)->handle($this->filters);

        expect($totals->total)->toBe(0);
    });
});

describe('tela', function (): void {
    it('mostra os cards de ativação com a régua escrita', function (): void {
        usageCompany('Alpha SA', seats: 10, employees: 5, usedNow: 2);

        Livewire::test(ActivationTotalsWidget::class)
            ->assertOk()
            ->assertSeeText('Taxa de ativação')
            ->assertSeeText('régua provisória');
    });

    it('lista as empresas com o destaque de baixa utilização', function (): void {
        usageCompany('Ociosa SA', seats: 10, employees: 10, usedNow: 1);

        Livewire::test(CompanyUsageTableWidget::class)
            ->assertOk()
            ->assertSeeText('Ociosa SA')
            ->assertSeeText('Nunca utilizaram');
    });

    it('monta os dois widgets na página', function (): void {
        Livewire::test(UsersAndUsage::class)
            ->assertOk()
            ->assertSeeLivewire(ActivationTotalsWidget::class)
            ->assertSeeLivewire(CompanyUsageTableWidget::class);
    });

    it('fecha para Admin comum', function (): void {
        actingAsAdmin();

        expect(UsersAndUsage::canAccess())->toBeFalse();
    });
});
