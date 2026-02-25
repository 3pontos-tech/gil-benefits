<?php

declare(strict_types=1);

use \TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;
use \App\Models\Users\User;

return [
    'permissions' => [
        Roles::Admin->value => [
            Company::class => [
                PermissionsEnum::ViewAny,
                PermissionsEnum::View,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
            User::class => [
                PermissionsEnum::ViewAny,
                PermissionsEnum::View,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
        ],
    ],
];
