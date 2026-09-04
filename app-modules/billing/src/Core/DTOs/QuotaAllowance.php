<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

use Carbon\CarbonImmutable;

/**
 * Quanto a pessoa tem direito por ciclo, em que dia o ciclo dela vira e sob qual
 * empresa isso foi resolvido.
 *
 * Os três andam juntos: o limite vem de um plano, a âncora vem do MESMO plano e a
 * empresa é a que decidiu qual plano responder. Resolver um sem os outros deixa a
 * porta aberta para misturar as fontes — quem trabalha em duas empresas teria o
 * limite de uma descontado pelos agendamentos das duas.
 */
final readonly class QuotaAllowance
{
    public function __construct(
        public int $limit,
        public ?CarbonImmutable $anchor,
        public ?string $companyId = null,
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
