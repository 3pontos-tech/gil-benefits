<?php

declare(strict_types=1);

use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\GetChurnRisk;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Enums\ChurnRiskLevel;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ChurnRiskWidget;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo(CarbonImmutable::create(2026, 8, 27, 12, 0, 0));
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

/**
 * Empresa com assinatura, N colaboradores registrados e M deles com consultoria
 * realizada no mês — o suficiente para o funil calcular a utilização.
 */
function companyWithUsage(string $name, int $quantity, int $registered, int $completed): Company
{
    $company = Company::factory()->create(['name' => $name]);

    $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => 'active',
        'quantity' => $quantity,
    ]);

    $employees = User::factory()->count($registered)->create();

    foreach ($employees as $index => $employee) {
        $company->employees()->attach($employee->getKey(), [
            'active' => true,
            'created_at' => now()->subMonths(2),
        ]);

        if ($index < $completed) {
            Appointment::factory()->create([
                'company_id' => $company->getKey(),
                'user_id' => $employee->getKey(),
                'status' => AppointmentStatus::Completed,
                'appointment_at' => now()->subDays(5),
            ]);
        }
    }

    return $company;
}

/**
 * Empresa barata e saudável, usada para ancorar a mediana.
 *
 * Sem ela, uma base de duas ou três empresas faz a mediana coincidir com o valor
 * da própria empresa avaliada, e ninguém fica "acima da mediana" — a régua da
 * story só tem sentido sobre uma base com dispersão.
 */
function medianAnchor(string $name = 'Âncora'): Company
{
    return companyWithUsage($name, 10, 10, 9);
}

describe('quem entra na lista', function (): void {
    it('exige baixo uso e valor acima da mediana ao mesmo tempo', function (): void {
        // Valor por assento: 10 assentos = R$ 449,00; 50 assentos = R$ 1.245,00.
        medianAnchor();
        companyWithUsage('Cara e ociosa', 50, 10, 1);   // 10% de uso, valor alto
        companyWithUsage('Barata e ociosa', 10, 10, 1); // 10% de uso, valor baixo
        companyWithUsage('Cara e ativa', 50, 10, 9);    // 90% de uso, valor alto

        $report = resolve(GetChurnRisk::class)->handle($this->filters);

        expect($report->rows->pluck('companyName')->all())->toBe(['Cara e ociosa']);
    });

    it('ignora empresa sem valor conhecido e avisa quantas ficaram de fora', function (): void {
        medianAnchor();
        companyWithUsage('Com valor', 50, 10, 1);
        Company::factory()->create(['name' => 'Sem valor']);

        $report = resolve(GetChurnRisk::class)->handle($this->filters);

        expect($report->companiesWithoutValue)->toBe(1)
            ->and($report->rows->pluck('companyName')->all())->not->toContain('Sem valor');
    });

    it('fica vazio quando todo mundo usa bem', function (): void {
        companyWithUsage('Saudável Um', 50, 10, 9);
        companyWithUsage('Saudável Dois', 10, 10, 8);

        expect(resolve(GetChurnRisk::class)->handle($this->filters)->isEmpty())->toBeTrue();
    });
});

describe('nível de risco', function (): void {
    it('gradua pela severidade da subutilização', function (float $usageRate, ChurnRiskLevel $expected): void {
        expect(ChurnRiskLevel::fromUsageRate($usageRate))->toBe($expected);
    })->with([
        'quase ninguém usa' => [5.0, ChurnRiskLevel::High],
        'limite do alto' => [19.9, ChurnRiskLevel::High],
        'início do médio' => [20.0, ChurnRiskLevel::Medium],
        'limite do médio' => [29.9, ChurnRiskLevel::Medium],
        'início do baixo' => [30.0, ChurnRiskLevel::Low],
        'quase na linha' => [39.9, ChurnRiskLevel::Low],
    ]);

    it('classifica a empresa da lista pelo uso dela', function (): void {
        medianAnchor();
        companyWithUsage('Crítica', 50, 10, 1);

        $row = resolve(GetChurnRisk::class)->handle($this->filters)->rows->first();

        expect($row->level)->toBe(ChurnRiskLevel::High)
            ->and($row->usageRate)->toBe(10.0);
    });
});

describe('ordenação e totais', function (): void {
    it('coloca o maior valor em risco primeiro', function (): void {
        medianAnchor('Âncora Um');
        medianAnchor('Âncora Dois');
        companyWithUsage('Menor', 50, 10, 1);
        companyWithUsage('Maior', 120, 10, 1);

        $names = resolve(GetChurnRisk::class)->handle($this->filters)->rows->pluck('companyName')->all();

        expect($names)->toBe(['Maior', 'Menor']);
    });

    it('soma o valor mensal em risco', function (): void {
        medianAnchor();
        companyWithUsage('Cara e ociosa', 50, 10, 1);

        $report = resolve(GetChurnRisk::class)->handle($this->filters);

        expect($report->rows)->toHaveCount(1)
            ->and($report->valueAtRiskCents())->toBe($report->rows->first()->monthlyValueCents);
    });
});

describe('utilização compartilhada com o engajamento', function (): void {
    it('usa a mesma régua do funil, realizadas sobre cadastrados', function (): void {
        medianAnchor();
        companyWithUsage('Metade usa', 50, 10, 5);

        $report = resolve(GetChurnRisk::class)->handle($this->filters);

        // 50% está acima da linha de 40%, então não entra na lista de risco.
        expect($report->isEmpty())->toBeTrue();
    });
});

describe('tela', function (): void {
    it('mostra a empresa em risco com o badge', function (): void {
        medianAnchor();
        companyWithUsage('Cara e ociosa', 50, 10, 1);

        Livewire::test(ChurnRiskWidget::class)
            ->assertOk()
            ->assertSeeText('Cara e ociosa')
            ->assertSeeText(ChurnRiskLevel::High->getLabel());
    });

    it('mostra o estado vazio com o texto da story', function (): void {
        companyWithUsage('Saudável', 50, 10, 9);

        Livewire::test(ChurnRiskWidget::class)
            ->assertOk()
            ->assertSeeText('Nenhuma empresa em risco de churn identificada no momento');
    });
});
