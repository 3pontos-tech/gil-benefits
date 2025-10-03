<?php

namespace TresPontosTech\Tenant\Enums;

enum CompanyRoleEnum: string
{
    case Owner = 'owner';

    case Manager = 'manager';
    case Employee = 'employee';

}
