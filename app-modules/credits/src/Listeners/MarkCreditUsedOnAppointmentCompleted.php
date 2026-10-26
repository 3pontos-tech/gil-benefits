<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Credits\Actions\MarkAppointmentCreditUsed;

final readonly class MarkCreditUsedOnAppointmentCompleted
{
    public function __construct(private MarkAppointmentCreditUsed $markUsed) {}

    public function handle(AppointmentCompleted $event): void
    {
        $this->markUsed->execute((string) $event->appointment->getKey());
    }
}
