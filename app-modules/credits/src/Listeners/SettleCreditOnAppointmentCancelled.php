<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Credits\Actions\MarkAppointmentCreditUsed;
use TresPontosTech\Credits\Actions\ReturnAppointmentCredit;

/**
 * A cancellation with notice gives the credit back; a late one spends it.
 */
final readonly class SettleCreditOnAppointmentCancelled
{
    public function __construct(
        private ReturnAppointmentCredit $returnCredit,
        private MarkAppointmentCreditUsed $markUsed,
    ) {}

    public function handle(AppointmentCancelled $event): void
    {
        $appointmentId = (string) $event->appointment->getKey();

        if ($event->appointment->status === AppointmentStatus::Cancelled) {
            $this->returnCredit->execute($appointmentId);

            return;
        }

        $this->markUsed->execute($appointmentId);
    }
}
