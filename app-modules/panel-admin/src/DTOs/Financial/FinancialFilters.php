<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Carbon\CarbonImmutable;

/**
 * Estado normalizado dos filtros do cockpit financeiro.
 *
 * Espelha o `EngagementFilters`, que já é o padrão de filtro de relatório do
 * painel Admin — a diferença é que aqui a janela é sempre mensal (D-17), então
 * o filtro guarda um `FinancialPeriod` em vez de duas datas soltas.
 */
final readonly class FinancialFilters
{
    /**
     * @param  array<int, string>  $companyIds  Vazio significa "todas as empresas".
     */
    public function __construct(
        public FinancialPeriod $period,
        public array $companyIds = [],
    ) {}

    /**
     * @param  array<string, mixed>|null  $pageFilters
     */
    public static function fromPageFilters(?array $pageFilters, ?CarbonImmutable $now = null): self
    {
        $month = data_get($pageFilters, 'month');

        $period = filled($month)
            ? self::periodFromMonth((string) $month, $now)
            : FinancialPeriod::currentMonth($now);

        /** @var array<int, string> $companyIds */
        $companyIds = array_values(array_filter(
            (array) data_get($pageFilters, 'companies', []),
            fn (mixed $id): bool => filled($id),
        ));

        return new self($period, $companyIds);
    }

    public function isFilteredByCompany(): bool
    {
        return $this->companyIds !== [];
    }

    /**
     * Impressão digital estável dos filtros, usada como segmento de chave de cache.
     */
    public function fingerprint(): string
    {
        $companyIds = $this->companyIds;
        sort($companyIds);

        return md5(implode('|', [$this->period->cacheKey(), ...$companyIds]));
    }

    /**
     * Aceita `2026-08` do seletor de mês e cai no mês corrente diante de
     * qualquer coisa que não seja isso — o filtro vem da URL e não pode
     * derrubar a página por causa de um valor colado à mão.
     */
    private static function periodFromMonth(string $month, ?CarbonImmutable $now): FinancialPeriod
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $month, $matches) !== 1) {
            return FinancialPeriod::currentMonth($now);
        }

        $year = (int) $matches[1];
        $monthNumber = (int) $matches[2];

        if ($monthNumber < 1 || $monthNumber > 12) {
            return FinancialPeriod::currentMonth($now);
        }

        return FinancialPeriod::month($year, $monthNumber);
    }
}
