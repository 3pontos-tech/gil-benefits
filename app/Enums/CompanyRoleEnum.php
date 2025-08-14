<?php

namespace App\Enums;

enum CompanyRoleEnum: string
{
    case Owner = 'owner';

    case Manager = 'manager';
    case Employee = 'employee';

}
