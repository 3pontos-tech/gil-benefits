<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Enums;

enum AppointmentListFilter: string
{
    case Upcoming = 'upcoming';
    case Pending = 'pending';
    case History = 'history';
}
