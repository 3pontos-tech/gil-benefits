<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * A janela mensal de cota de uma pessoa, derivada da data de contratação.
 *
 * O passo é de mês-calendário a partir da âncora ORIGINAL, com clamp e sem drift:
 * âncora dia 31 vira 28/fev e VOLTA para 31/mar. Somar mês em cima do resultado
 * anterior faria a data de virada escorregar para trás permanentemente, e quem
 * contratou dia 31 passaria a renovar dia 28 para sempre.
 *
 * `start` é inclusivo e `end` é exclusivo — `end` é o instante em que o ciclo
 * seguinte começa, de modo que a virada nunca pertença aos dois.
 */
final readonly class QuotaCycle
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    /**
     * O ciclo que contém `$at`, contado sempre a partir da âncora original.
     *
     * Uma âncora no futuro devolve o primeiro ciclo, ainda não começado. O ajuste
     * por laço existe porque `diffInMonths` erra por um nas bordas de mês curto.
     */
    public static function forAnchor(DateTimeInterface $anchor, ?DateTimeInterface $at = null): self
    {
        $anchor = CarbonImmutable::instance($anchor)->startOfDay();
        $at = $at instanceof DateTimeInterface ? CarbonImmutable::instance($at) : CarbonImmutable::now();

        if ($at->lessThan($anchor)) {
            return new self($anchor, $anchor->addMonthNoOverflow());
        }

        $months = max(0, (int) $anchor->diffInMonths($at));

        while ($months > 0 && $anchor->addMonthsNoOverflow($months)->greaterThan($at)) {
            --$months;
        }

        while ($anchor->addMonthsNoOverflow($months + 1)->lessThanOrEqualTo($at)) {
            ++$months;
        }

        return new self(
            $anchor->addMonthsNoOverflow($months),
            $anchor->addMonthsNoOverflow($months + 1),
        );
    }

    /**
     * Se o instante cai dentro da janela — início inclusivo, fim exclusivo.
     */
    public function contains(DateTimeInterface $moment): bool
    {
        $moment = CarbonImmutable::instance($moment);

        return $moment->greaterThanOrEqualTo($this->start) && $moment->lessThan($this->end);
    }

    /**
     * Se a janela já virou, ou seja, se este ciclo não é mais o corrente.
     */
    public function hasClosed(?DateTimeInterface $at = null): bool
    {
        $at = $at instanceof DateTimeInterface ? CarbonImmutable::instance($at) : CarbonImmutable::now();

        return $at->greaterThanOrEqualTo($this->end);
    }
}
