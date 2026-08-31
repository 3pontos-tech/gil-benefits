<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

/**
 * Repasse devido a um consultor no mês (STORY-239).
 *
 * `costPerAppointmentCents` viaja junto para a tela poder mostrar de onde o
 * número saiu — valor próprio do consultor ou o padrão configurado. Sem isso,
 * um repasse divergente entre dois consultores parece erro.
 */
final readonly class PayoutRow
{
    public function __construct(
        public string $consultantId,
        public string $consultantName,
        public int $completed,
        public int $cancelledLate,
        public int $noShow,
        public ?int $costPerAppointmentCents,
        public bool $usesDefaultCost,
    ) {}

    /**
     * Consultorias que consumiram crédito do cliente.
     *
     * A régua é a do PO: realizadas, mais os desfechos que fazem o cliente
     * perder o crédito — cancelamento fora da regra e não comparecimento.
     * Confere com o código: o crédito é consumido na confirmação e só volta
     * em cancelamento dentro da regra.
     */
    public function billable(): int
    {
        return $this->completed + $this->cancelledLate + $this->noShow;
    }

    /**
     * `null` quando não há custo definido — nem no consultor, nem no padrão.
     * Zero diria que o parceiro trabalhou de graça.
     */
    public function payoutCents(): ?int
    {
        if ($this->costPerAppointmentCents === null) {
            return null;
        }

        return $this->billable() * $this->costPerAppointmentCents;
    }

    public function hasCost(): bool
    {
        return $this->costPerAppointmentCents !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'consultant_id' => $this->consultantId,
            'consultant_name' => $this->consultantName,
            'completed' => $this->completed,
            'cancelled_late' => $this->cancelledLate,
            'no_show' => $this->noShow,
            'billable' => $this->billable(),
            'cost_per_appointment_cents' => $this->costPerAppointmentCents,
            'uses_default_cost' => $this->usesDefaultCost,
            'payout_cents' => $this->payoutCents(),
        ];
    }
}
