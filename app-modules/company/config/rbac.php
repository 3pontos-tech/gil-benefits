<?php

declare(strict_types=1);

use \TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;

return [
    'permissions' => [
        Roles::SuperAdmin->value => [
            Company::class => [
                PermissionsEnum::ViewAny,
                PermissionsEnum::View,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
        ],
    ],
];
