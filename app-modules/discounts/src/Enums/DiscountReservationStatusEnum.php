<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

enum DiscountReservationStatusEnum: string
{
    case Reserved = 'reserved';
    case Ineligible = 'ineligible';
    case Conflict = 'conflict';
}
