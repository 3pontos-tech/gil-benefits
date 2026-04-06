<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';

    case Company = 'company';

    case Consultant = 'consultant';

    case Employee = 'employee';
}
