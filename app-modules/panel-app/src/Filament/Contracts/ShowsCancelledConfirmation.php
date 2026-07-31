<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Contracts;

use TresPontosTech\Appointments\Models\Appointment;

/**
 * Um host da CancelAppointmentAction que, além de cancelar, abre a tela de
 * sucesso do fluxo. A action depende deste contrato para saber que pode trocar
 * a confirmação pela tela de sucesso; hosts que não o implementam caem no toast.
 */
interface ShowsCancelledConfirmation
{
    public function confirmAppointmentCancellation(Appointment $appointment, bool $keepsCredit): void;
}
