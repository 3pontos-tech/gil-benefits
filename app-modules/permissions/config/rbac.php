<?php

declare(strict_types=1);

use TresPontosTech\Permissions\Permission;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Role;
use TresPontosTech\Permissions\Roles;

return [
    'permissions' => [
        Roles::SuperAdmin->value => [
            Role::class => PermissionsEnum::cases(),
            Permission::class => [
                PermissionsEnum::ViewAny,
                PermissionsEnum::View,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
                PermissionsEnum::ForceDelete,
                PermissionsEnum::Restore,
            ],
        ],
    ],
];
