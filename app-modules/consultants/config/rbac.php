<?php

declare(strict_types=1);

use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;

return [
    'permissions' => [
        Roles::Admin->value => [
            Consultant::class => [
                PermissionsEnum::ViewAny,
                PermissionsEnum::View,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
        ],
    ],
];
