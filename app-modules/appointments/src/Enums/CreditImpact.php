<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Enums;

enum CreditImpact: string
{
    case Consumed = 'consumed';
    case None = 'none';
}
