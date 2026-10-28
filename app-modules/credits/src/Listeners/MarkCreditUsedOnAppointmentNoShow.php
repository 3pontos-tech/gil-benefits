<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Appointments\Events\AppointmentNoShow;
use TresPontosTech\Credits\Actions\MarkAppointmentCreditUsed;

final readonly class MarkCreditUsedOnAppointmentNoShow
{
    public function __construct(private MarkAppointmentCreditUsed $markUsed) {}

    public function handle(AppointmentNoShow $event): void
    {
        $this->markUsed->execute((string) $event->appointment->getKey());
    }
}
