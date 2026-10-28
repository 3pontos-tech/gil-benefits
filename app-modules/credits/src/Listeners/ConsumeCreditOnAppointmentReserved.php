<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Appointments\Events\AppointmentReserved;
use TresPontosTech\Credits\Actions\ConsumeCredit;
use TresPontosTech\Credits\DTOs\CreditDTO;

final readonly class ConsumeCreditOnAppointmentReserved
{
    public function __construct(private ConsumeCredit $consumeCredit) {}

    public function handle(AppointmentReserved $event): void
    {
        if ($event->paidWithQuota) {
            return;
        }

        $this->consumeCredit->execute(new CreditDTO(
            holderId: $event->appointment->user_id,
            companyId: $event->appointment->company_id,
            appointmentId: $event->appointment->getKey(),
        ));
    }
}
