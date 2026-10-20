<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Enums\Employee\Appointments;

enum AppointmentListFilter: string
{
    case Upcoming = 'upcoming';
    case Pending = 'pending';
    case History = 'history';
}
