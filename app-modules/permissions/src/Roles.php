<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions;

enum Roles: string
{
    case SuperAdmin = 'super_admin';

    case Admin = 'admin';
    case CompanyOwner = 'company_owner';

    case User = 'user';
    case Employee = 'employee';
}
