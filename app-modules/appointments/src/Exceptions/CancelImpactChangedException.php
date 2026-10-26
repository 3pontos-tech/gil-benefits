<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Exceptions;

use RuntimeException;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O colaborador confirmou o cancelamento com o crédito voltando, mas o prazo passou entre a
 * confirmação e o pedido: cancelar agora consumiria o crédito. Nada é cancelado; quem chamou
 * pergunta de novo com o impacto atual.
 */
class CancelImpactChangedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('appointments::resources.appointments.exceptions.cancel_impact_changed', [
            'hours' => Appointment::CANCELLATION_WINDOW_HOURS,
        ]));
    }
}
