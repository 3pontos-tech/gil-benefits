<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialPeriod;

describe('fronteira do mês', function (): void {
    it('mantém o fuso da aplicação em São Paulo', function (): void {
        expect(config('app.timezone'))->toBe('America/Sao_Paulo');
    })->note('Trocar app.timezone desloca o fechamento do mês financeiro — D-17.');

    it('cobre o mês corrente de ponta a ponta', function (): void {
        $period = FinancialPeriod::currentMonth(CarbonImmutable::create(2026, 8, 27, 15, 30));

        expect($period->start->toDateTimeString())->toBe('2026-08-01 00:00:00')
            ->and($period->end->toDateTimeString())->toBe('2026-08-31 23:59:59');
    });

    it('constrói um mês específico', function (): void {
        $period = FinancialPeriod::month(2026, 2);

        expect($period->start->toDateString())->toBe('2026-02-01')
            ->and($period->end->toDateString())->toBe('2026-02-28');
    });

    it('recusa mês fora do intervalo', function (int $month): void {
        FinancialPeriod::month(2026, $month);
    })->with([0, 13])->throws(InvalidArgumentException::class);
});

describe('janelas de vários meses', function (): void {
    it('inclui o mês corrente na contagem', function (): void {
        $period = FinancialPeriod::lastMonths(12, CarbonImmutable::create(2026, 8, 27));

        expect($period->start->toDateString())->toBe('2025-09-01')
            ->and($period->end->toDateString())->toBe('2026-08-31');
    });

    it('quebra a janela em meses, do mais antigo ao mais recente', function (): void {
        $months = FinancialPeriod::lastMonths(3, CarbonImmutable::create(2026, 8, 27))->eachMonth();

        expect($months)->toHaveCount(3)
            ->and($months[0]->start->toDateString())->toBe('2026-06-01')
            ->and($months[2]->start->toDateString())->toBe('2026-08-01');
    });

    it('não transborda ao voltar de mês longo para mês curto', function (): void {
        $period = FinancialPeriod::currentMonth(CarbonImmutable::create(2026, 3, 31));

        expect($period->previous()->start->toDateString())->toBe('2026-02-01')
            ->and($period->previous()->end->toDateString())->toBe('2026-02-28');
    });

    it('responde se um instante cai na janela', function (): void {
        $period = FinancialPeriod::month(2026, 8);

        expect($period->contains(CarbonImmutable::create(2026, 8, 31, 23, 59, 59)))->toBeTrue()
            ->and($period->contains(CarbonImmutable::create(2026, 9, 1)))->toBeFalse();
    });

    it('gera chave de cache estável por janela', function (): void {
        expect(FinancialPeriod::month(2026, 8)->cacheKey())
            ->toBe(FinancialPeriod::month(2026, 8)->cacheKey())
            ->not->toBe(FinancialPeriod::month(2026, 7)->cacheKey());
    });
});

describe('filtros da página', function (): void {
    it('cai no mês corrente sem filtro', function (): void {
        $now = CarbonImmutable::create(2026, 8, 27);

        expect(FinancialFilters::fromPageFilters(null, $now)->period->start->toDateString())
            ->toBe('2026-08-01');
    });

    it('aceita o mês escolhido no seletor', function (): void {
        $filters = FinancialFilters::fromPageFilters(['month' => '2026-05']);

        expect($filters->period->start->toDateString())->toBe('2026-05-01');
    });

    it('ignora mês inválido vindo da URL em vez de quebrar a página', function (string $month): void {
        $now = CarbonImmutable::create(2026, 8, 27);

        expect(FinancialFilters::fromPageFilters(['month' => $month], $now)->period->start->toDateString())
            ->toBe('2026-08-01');
    })->with([
        'mês zero' => '2026-00',
        'mês treze' => '2026-13',
        'texto solto' => 'agosto',
        'formato errado' => '08/2026',
    ]);

    it('normaliza a lista de empresas descartando vazios', function (): void {
        $filters = FinancialFilters::fromPageFilters(['companies' => ['abc', null, '', 'def']]);

        expect($filters->companyIds)->toBe(['abc', 'def'])
            ->and($filters->isFilteredByCompany())->toBeTrue();
    });

    it('gera a mesma impressão digital independente da ordem das empresas', function (): void {
        $a = FinancialFilters::fromPageFilters(['month' => '2026-05', 'companies' => ['b', 'a']]);
        $b = FinancialFilters::fromPageFilters(['month' => '2026-05', 'companies' => ['a', 'b']]);

        expect($a->fingerprint())->toBe($b->fingerprint());
    });

    it('muda a impressão digital quando o mês muda', function (): void {
        $a = FinancialFilters::fromPageFilters(['month' => '2026-05']);
        $b = FinancialFilters::fromPageFilters(['month' => '2026-06']);

        expect($a->fingerprint())->not->toBe($b->fingerprint());
    });
});
