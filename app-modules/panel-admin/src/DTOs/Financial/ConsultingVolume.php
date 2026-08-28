<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

/**
 * Volume de consultorias do mês (STORY-238).
 *
 * `cancelledLate` viaja separado do total de canceladas de propósito: ele soma
 * em "Canceladas" na tela, como a story pede, mas consome crédito — esconder a
 * contagem apagaria justamente o impacto financeiro do cancelamento tardio.
 */
final readonly class ConsultingVolume
{
    public function __construct(
        public int $scheduled,
        public int $completed,
        public int $cancelled,
        public int $cancelledLate,
        public int $noShow,
    ) {}

    /**
     * Taxa de Realização = Realizadas ÷ Agendadas × 100 (cenário 3 da story).
     *
     * Nula sem nenhuma consultoria agendada: 0% diria que ninguém compareceu,
     * quando na verdade não houve o que comparecer.
     */
    public function completionRate(): ?float
    {
        if ($this->scheduled < 1) {
            return null;
        }

        return round(($this->completed / $this->scheduled) * 100, 1);
    }

    public function isEmpty(): bool
    {
        return $this->scheduled < 1;
    }
}
