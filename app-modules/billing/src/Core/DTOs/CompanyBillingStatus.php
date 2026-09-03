<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

use Carbon\CarbonImmutable;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;

/**
 * Situação de cobrança de uma empresa: status unificado e quando ela cobra de novo.
 *
 * Os dois viajam juntos porque saem da mesma leitura — separá-los em duas chamadas
 * dobraria a consulta em uma listagem de todas as empresas.
 *
 * `nextChargeAt` é sempre **estimado** (FLM-41, D-05): o sistema não guarda data de
 * próxima cobrança em lugar nenhum, então ela é projetada a partir do início do
 * ciclo. Quem exibe precisa rotular.
 */
final readonly class CompanyBillingStatus
{
    public function __construct(
        public CompanyFinancialStatusEnum $status,
        public ?CarbonImmutable $nextChargeAt,
    ) {}

    public static function none(): self
    {
        return new self(CompanyFinancialStatusEnum::None, null);
    }

    /**
     * Se a próxima cobrança cai dentro da janela, contada a partir de agora.
     *
     * Empresa cancelada não renova, então nunca entra — o resolver já devolve
     * `nextChargeAt` nulo nesse caso, mas a guarda fica explícita aqui porque
     * este método é o que alimenta os alertas da STORY-237.
     */
    public function renewsWithin(int $days, ?CarbonImmutable $now = null): bool
    {
        if (! $this->nextChargeAt instanceof CarbonImmutable) {
            return false;
        }

        $now ??= CarbonImmutable::now();

        return $this->nextChargeAt->betweenIncluded($now, $now->addDays($days));
    }
}
