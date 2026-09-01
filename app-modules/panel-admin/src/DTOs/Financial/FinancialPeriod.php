<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Janela mensal do cockpit financeiro (FLM-41, D-17).
 *
 * O mês é a unidade do épico inteiro: MRR, comparativo com o mês anterior,
 * evolução de 12 meses e status de pagamento por mês. Ter os limites em um lugar
 * só evita a classe de bug em que o card e o gráfico discordam por causa de um
 * `startOfMonth` a mais ou a menos.
 *
 * A fronteira do mês segue o fuso da aplicação, que é `America/Sao_Paulo`. Isso
 * não é convertido aqui de propósito: converter seria no-op hoje e mascararia
 * uma mudança de configuração. Em vez disso há teste fixando o fuso, para que
 * trocar `app.timezone` quebre alto em vez de deslocar o fechamento em silêncio.
 */
final readonly class FinancialPeriod
{
    private function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public static function currentMonth(?CarbonImmutable $now = null): self
    {
        $now ??= CarbonImmutable::now();

        return new self($now->startOfMonth(), $now->endOfMonth());
    }

    public static function month(int $year, int $month): self
    {
        throw_if($month < 1 || $month > 12, InvalidArgumentException::class, 'Mês precisa estar entre 1 e 12.');

        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();

        return new self($start, $start->endOfMonth());
    }

    /**
     * Os `$months` meses fechados mais recentes, incluindo o corrente.
     */
    public static function lastMonths(int $months, ?CarbonImmutable $now = null): self
    {
        throw_if($months < 1, InvalidArgumentException::class, 'A quantidade de meses precisa ser ao menos 1.');

        $now ??= CarbonImmutable::now();

        return new self(
            $now->subMonthsNoOverflow($months - 1)->startOfMonth(),
            $now->endOfMonth(),
        );
    }

    public function previous(): self
    {
        $start = $this->start->subMonthNoOverflow()->startOfMonth();

        return new self($start, $start->endOfMonth());
    }

    /**
     * Cada mês da janela, do mais antigo para o mais recente.
     *
     * @return list<self>
     */
    public function eachMonth(): array
    {
        $months = [];
        $cursor = $this->start->startOfMonth();

        while ($cursor->lessThanOrEqualTo($this->end)) {
            $months[] = new self($cursor, $cursor->endOfMonth());
            $cursor = $cursor->addMonthNoOverflow();
        }

        return $months;
    }

    public function contains(CarbonImmutable $moment): bool
    {
        return $moment->betweenIncluded($this->start, $this->end);
    }

    public function label(): string
    {
        return $this->start->translatedFormat('M/Y');
    }

    public function cacheKey(): string
    {
        return sprintf('%s_%s', $this->start->toDateString(), $this->end->toDateString());
    }
}
