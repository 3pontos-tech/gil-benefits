<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\DTO;

use TresPontosTech\Appointments\Models\Appointment;

final readonly class RescheduledAppointment
{
    /**
     * @param  bool  $calendarSynced  false quando a consulta foi movida mas o Google Calendar não acompanhou.
     */
    public function __construct(public Appointment $appointment, public bool $calendarSynced) {}
}
