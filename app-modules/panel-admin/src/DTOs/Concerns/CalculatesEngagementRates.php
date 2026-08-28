<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Concerns;

use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Conversion rates between adjacent funnel steps, defined once so the
 * consolidated card and the per-company row can never disagree on a denominator.
 *
 * @property-read int $seats
 * @property-read int $registered
 * @property-read int $withAppointment
 * @property-read int $withCompletedAppointment
 * @property-read int $withRecurrence
 */
trait CalculatesEngagementRates
{
    public function registrationRate(): ?float
    {
        return EngagementNumber::rate($this->registered, $this->seats);
    }

    public function schedulingRate(): ?float
    {
        return EngagementNumber::rate($this->withAppointment, $this->registered);
    }

    public function completionRate(): ?float
    {
        return EngagementNumber::rate($this->withCompletedAppointment, $this->withAppointment);
    }

    public function recurrenceRate(): ?float
    {
        return EngagementNumber::rate($this->withRecurrence, $this->withCompletedAppointment);
    }

    /**
     * Utilização da empresa: beneficiários que realizaram consultoria sobre os
     * cadastrados.
     *
     * Distinta da taxa de conclusão, que divide realizadas por agendadas. Aqui a
     * pergunta é outra — quanto da base efetivamente usa o benefício — e é a
     * régua que o cockpit financeiro aplica no risco de churn (STORY-235) e no
     * destaque de baixa utilização (STORY-241). Mora neste trait pelo mesmo
     * motivo das demais: para as telas nunca discordarem do denominador.
     */
    public function usageRate(): ?float
    {
        return EngagementNumber::rate($this->withCompletedAppointment, $this->registered);
    }
}
