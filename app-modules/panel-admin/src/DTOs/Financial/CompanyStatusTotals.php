<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Carbon\CarbonImmutable;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;

/**
 * Contagem de empresas por status, com as renovações próximas (STORY-233).
 */
final readonly class CompanyStatusTotals
{
    /**
     * @param  array<string, int>  $byStatus  Contagem indexada pelo valor do enum.
     */
    public function __construct(
        public array $byStatus,
        public int $renewingIn30Days,
        public int $renewingIn7Days,
        public CarbonImmutable $generatedAt,
    ) {}

    public function count(CompanyFinancialStatusEnum $status): int
    {
        return $this->byStatus[$status->value] ?? 0;
    }

    /**
     * Base de clientes viva: ativas, em trial e inadimplentes.
     *
     * Inadimplente entra porque ainda é cliente — quem quer a visão de caixa
     * olha o módulo de cobranças, não este card.
     */
    public function living(): int
    {
        return array_sum(array_map(
            fn (CompanyFinancialStatusEnum $status): int => $this->count($status),
            CompanyFinancialStatusEnum::livingCases(),
        ));
    }
}
