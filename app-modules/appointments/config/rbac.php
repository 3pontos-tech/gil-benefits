<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;

return [
    'permissions' => [
        Roles::Admin->value => [
            AppointmentRecord::class => [
                PermissionsEnum::ViewAny,
                PermissionsEnum::View,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
        ],
        Roles::Consultant->value => [
            AppointmentRecord::class => [
                PermissionsEnum::ViewAny,
                PermissionsEnum::View,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
        ],
        Roles::Employee->value => [
            AppointmentRecord::class => [
                PermissionsEnum::View,
                PermissionsEnum::ViewAny,
            ],
        ],
        Roles::User->value => [
            AppointmentRecord::class => [
                PermissionsEnum::View,
                PermissionsEnum::ViewAny,
            ],
        ],
    ],
];
