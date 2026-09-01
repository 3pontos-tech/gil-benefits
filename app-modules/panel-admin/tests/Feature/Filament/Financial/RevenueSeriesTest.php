<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueBreakdown;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueSeries;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\RevenueByPlanChartWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\RevenueRankingTableWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\RevenueSeriesChartWidget;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo(CarbonImmutable::create(2026, 8, 27, 12, 0, 0));
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

function seriesCompany(int $seats, CarbonImmutable $createdAt, ?CarbonImmutable $endsAt = null, ?string $name = null): Company
{
    $company = Company::factory()->create($name === null ? [] : ['name' => $name]);

    $subscription = $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => 'active',
        'quantity' => $seats,
        'ends_at' => $endsAt,
    ]);

    $subscription->forceFill(['created_at' => $createdAt])->save();

    return $company;
}

describe('série de meses', function (): void {
    it('devolve um ponto por mês pedido', function (int $months): void {
        seriesCompany(10, CarbonImmutable::create(2025, 1, 1));

        expect(resolve(GetRevenueSeries::class)->handle($this->filters, $months))->toHaveCount($months);
    })->with([3, 6, 12]);

    it('termina no mês corrente', function (): void {
        seriesCompany(10, CarbonImmutable::create(2025, 1, 1));

        $points = resolve(GetRevenueSeries::class)->handle($this->filters, 3);

        expect($points->last()->isReconstructed)->toBeFalse();
    });

    it('marca todo mês anterior como reconstruído', function (): void {
        seriesCompany(10, CarbonImmutable::create(2025, 1, 1));

        $points = resolve(GetRevenueSeries::class)->handle($this->filters, 3);

        expect($points->first()->isReconstructed)->toBeTrue()
            ->and($points[1]->isReconstructed)->toBeTrue();
    });
});

describe('vigência ao longo da série', function (): void {
    it('faz a assinatura aparecer só a partir do mês em que nasceu', function (): void {
        seriesCompany(10, CarbonImmutable::create(2026, 7, 15));

        $points = resolve(GetRevenueSeries::class)->handle($this->filters, 3);

        expect($points[0]->totalCents)->toBe(0)   // junho
            ->and($points[1]->totalCents)->toBe(44900)  // julho
            ->and($points[2]->totalCents)->toBe(44900); // agosto
    });

    it('faz a assinatura sumir depois do mês em que terminou', function (): void {
        seriesCompany(10, CarbonImmutable::create(2026, 1, 1), CarbonImmutable::create(2026, 7, 10));

        $points = resolve(GetRevenueSeries::class)->handle($this->filters, 3);

        expect($points[0]->totalCents)->toBe(44900)  // junho
            ->and($points[1]->totalCents)->toBe(44900)  // julho, terminou no dia 10
            ->and($points[2]->totalCents)->toBe(0);     // agosto
    });

    it('calcula a variação contra o mês anterior da própria série', function (): void {
        seriesCompany(10, CarbonImmutable::create(2026, 1, 1));
        seriesCompany(10, CarbonImmutable::create(2026, 8, 1));

        $points = resolve(GetRevenueSeries::class)->handle($this->filters, 3);

        expect($points[2]->variation)->toBe(100.0);
    });

    it('não inventa variação sobre um mês zerado', function (): void {
        seriesCompany(10, CarbonImmutable::create(2026, 8, 1));

        $points = resolve(GetRevenueSeries::class)->handle($this->filters, 3);

        expect($points[0]->variation)->toBeNull()
            ->and($points[2]->variation)->toBeNull();
    });
});

describe('receita por plano e ranking', function (): void {
    it('ordena o ranking do maior para o menor', function (): void {
        seriesCompany(10, now()->toImmutable()->subMonths(2), name: 'Pequena');
        seriesCompany(120, now()->toImmutable()->subMonths(2), name: 'Grande');

        $ranking = resolve(GetRevenueBreakdown::class)->handle($this->filters)->ranking;

        expect($ranking->first()->companyName)->toBe('Grande')
            ->and($ranking->last()->companyName)->toBe('Pequena');
    });

    it('soma a receita por plano', function (): void {
        seriesCompany(10, now()->toImmutable()->subMonths(2));
        seriesCompany(10, now()->toImmutable()->subMonths(2));

        $breakdown = resolve(GetRevenueBreakdown::class)->handle($this->filters);

        expect($breakdown->totalCents)->toBe(89800)
            ->and(array_sum($breakdown->byPlan))->toBe(89800);
    });

    it('dispara o alerta quando uma empresa passa de 30% da receita', function (): void {
        seriesCompany(120, now()->toImmutable()->subMonths(2), name: 'Dominante');
        seriesCompany(10, now()->toImmutable()->subMonths(2));

        $breakdown = resolve(GetRevenueBreakdown::class)->handle($this->filters);

        expect($breakdown->hasConcentrationAlert())->toBeTrue()
            ->and($breakdown->topCompany()->companyName)->toBe('Dominante');
    });

    it('não dispara o alerta com a receita bem distribuída', function (): void {
        foreach (range(1, 5) as $i) {
            seriesCompany(10, now()->toImmutable()->subMonths(2));
        }

        expect(resolve(GetRevenueBreakdown::class)->handle($this->filters)->hasConcentrationAlert())->toBeFalse();
    });

    it('deixa contrato sem valor fora do ranking', function (): void {
        seriesCompany(10, now()->toImmutable()->subMonths(2), name: 'Com valor');
        Company::factory()->create(['name' => 'Sem valor']);

        $names = resolve(GetRevenueBreakdown::class)->handle($this->filters)->ranking->pluck('companyName')->all();

        expect($names)->toBe(['Com valor']);
    });
});

describe('tela', function (): void {
    it('renderiza o gráfico de evolução com o aviso de reconstrução', function (): void {
        seriesCompany(10, now()->toImmutable()->subMonths(3));

        Livewire::test(RevenueSeriesChartWidget::class)
            ->assertOk()
            ->assertSeeText('Evolução da receita')
            ->assertSeeText('reconstruídos');
    });

    it('troca a janela do gráfico pelo filtro', function (): void {
        seriesCompany(10, now()->toImmutable()->subMonths(6));

        Livewire::test(RevenueSeriesChartWidget::class)
            ->set('filter', '3')
            ->assertOk()
            ->assertSet('filter', '3');
    });

    it('renderiza o gráfico por plano e o ranking', function (): void {
        seriesCompany(10, now()->toImmutable()->subMonths(2), name: 'Alpha SA');

        Livewire::test(RevenueByPlanChartWidget::class)->assertOk();
        Livewire::test(RevenueRankingTableWidget::class)
            ->assertOk()
            ->assertSeeText('Alpha SA');
    });

    it('mostra o alerta de concentração no ranking', function (): void {
        seriesCompany(120, now()->toImmutable()->subMonths(2), name: 'Dominante');
        seriesCompany(10, now()->toImmutable()->subMonths(2));

        Livewire::test(RevenueRankingTableWidget::class)
            ->assertOk()
            ->assertSeeText('Alta concentração de receita');
    });
});
