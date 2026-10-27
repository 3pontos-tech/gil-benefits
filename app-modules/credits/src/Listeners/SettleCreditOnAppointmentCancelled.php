<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use LogicException;
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

        match ($event->appointment->status) {
            AppointmentStatus::Cancelled => $this->returnCredit->execute($appointmentId),
            AppointmentStatus::CancelledLate => $this->markUsed->execute($appointmentId),
            default => throw new LogicException(sprintf('No credit rule for a cancellation with status "%s".', $event->appointment->status->value)),
        };
    }
}
