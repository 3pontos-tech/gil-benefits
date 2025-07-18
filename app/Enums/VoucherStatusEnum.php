<?php

namespace App\Enums;

enum VoucherStatusEnum : string
{
    case Pending = 'pending';
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';

}
