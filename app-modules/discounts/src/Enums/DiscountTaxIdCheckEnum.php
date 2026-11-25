<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

enum DiscountTaxIdCheckEnum: string
{
    case Matches = 'matches';
    case Mismatch = 'mismatch';
    case Locked = 'locked';
}
