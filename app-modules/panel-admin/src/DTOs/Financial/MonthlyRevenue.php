<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

/**
 * Receita de um mês, separada por origem (FLM-41, D-11).
 *
 * B2B e avulso vivem em campos distintos porque só o B2B entra em ranking e em
 * concentração: a empresa-balde dos avulsos ficaria eternamente em primeiro
 * lugar e dispararia o alerta de concentração todo mês sem significar nada.
 */
final readonly class MonthlyRevenue
{
    public function __construct(
        public FinancialPeriod $period,
        public int $b2bCents,
        public int $standaloneCents,
        public int $payingCompanies,
        public int $payingUsers,
        public int $companiesWithKnownValue,
    ) {}

    public function totalCents(): int
    {
        return $this->b2bCents + $this->standaloneCents;
    }

    /**
     * Ticket médio B2B: mesmo universo no numerador e no denominador.
     *
     * Empresa sem valor conhecido fica fora dos dois lados — incluí-la só no
     * denominador deprimiria o ticket, e atribuir-lhe zero seria inventar receita
     * que não existe (D-01).
     */
    public function averageTicketCents(): ?int
    {
        if ($this->companiesWithKnownValue < 1) {
            return null;
        }

        return intdiv($this->b2bCents, $this->companiesWithKnownValue);
    }

    /**
     * Variação percentual contra outro mês, em pontos percentuais.
     *
     * Devolve `null` quando não há base de comparação — a tela exibe travessão e
     * nenhuma seta, em vez de um 0% fabricado que seria lido como estabilidade.
     */
    public function variationAgainst(?self $previous, string $metric = 'total'): ?float
    {
        if (! $previous instanceof self) {
            return null;
        }

        $before = $previous->metric($metric);
        $now = $this->metric($metric);

        if ($before === 0) {
            return null;
        }

        return round((($now - $before) / $before) * 100, 1);
    }

    public function metric(string $metric): int
    {
        return match ($metric) {
            'b2b' => $this->b2bCents,
            'standalone' => $this->standaloneCents,
            'paying_companies' => $this->payingCompanies,
            'paying_users' => $this->payingUsers,
            'average_ticket' => $this->averageTicketCents() ?? 0,
            default => $this->totalCents(),
        };
    }
}
