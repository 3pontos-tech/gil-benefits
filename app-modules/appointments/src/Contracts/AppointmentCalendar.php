<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Contracts;

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;

/**
 * The consultant's external calendar, as the appointments module sees it. Every call is
 * best-effort and answers whether the calendar ended up in sync; it never throws.
 */
interface AppointmentCalendar
{
    public function placeForCurrentConsultant(Appointment $appointment): bool;

    public function reschedule(Appointment $appointment): bool;

    public function removeFrom(Appointment $appointment, ?Consultant $consultant): bool;
}
