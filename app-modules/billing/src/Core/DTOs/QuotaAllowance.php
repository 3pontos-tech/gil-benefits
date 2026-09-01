<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

use Carbon\CarbonImmutable;

/**
 * Quanto a pessoa tem direito por ciclo e em que dia o ciclo dela vira.
 *
 * Os dois andam juntos: o limite vem de um plano e a âncora vem do MESMO plano,
 * então resolver um sem o outro deixa a porta aberta para misturar as fontes.
 */
final readonly class QuotaAllowance
{
    public function __construct(
        public int $limit,
        public ?CarbonImmutable $anchor,
    ) {}

    public static function none(): self
    {
        return new self(0, null);
    }

    /**
     * Sem direito a cota — por não ter plano, ou por o plano não conceder consulta.
     */
    public function isEmpty(): bool
    {
        return $this->limit <= 0 || ! $this->anchor instanceof CarbonImmutable;
    }
}
