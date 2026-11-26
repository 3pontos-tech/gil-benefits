<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

enum DiscountIneligibilityReasonEnum: string
{
    case DiscountInactive = 'discount_inactive';
    case ExistingAccount = 'existing_account';
    case AlreadyGranted = 'already_granted';
    case AlreadyClaimed = 'already_claimed';
    case Revoked = 'revoked';
    case Expired = 'expired';
}
