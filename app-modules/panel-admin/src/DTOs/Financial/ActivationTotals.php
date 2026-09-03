<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Utilização agregada da base (STORY-242).
 *
 * A régua é a da decisão D-09 e está escrita na tela: sem acesso é quem nunca
 * verificou o e-mail, ativo é quem realizou consultoria no período, inativo é o
 * resto. São proxies — `last_login_at` já está sendo gravado para que a régua
 * possa evoluir sem perder o histórico, mas ainda não alimenta nada.
 */
final readonly class ActivationTotals
{
    public function __construct(
        public int $total,
        public int $active,
        public int $inactive,
        public int $withoutAccess,
    ) {}

    public function activationRate(): ?float
    {
        return EngagementNumber::rate($this->active, $this->total);
    }

    public function metric(string $metric): int
    {
        return match ($metric) {
            'active' => $this->active,
            'inactive' => $this->inactive,
            'without_access' => $this->withoutAccess,
            default => $this->total,
        };
    }

    /**
     * Variação contra outro mês, nula quando não há base de comparação.
     */
    public function variationAgainst(?self $previous, string $metric): ?float
    {
        if (! $previous instanceof self) {
            return null;
        }

        $before = $previous->metric($metric);

        if ($before === 0) {
            return null;
        }

        return round((($this->metric($metric) - $before) / $before) * 100, 1);
    }
}
