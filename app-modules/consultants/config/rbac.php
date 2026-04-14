<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
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
        Roles::Consultant->value => [
            Appointment::class => [
                PermissionsEnum::View,
            ],
            Document::class => [
                PermissionsEnum::View,
                PermissionsEnum::ViewAny,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
            DocumentShare::class => [
                PermissionsEnum::View,
                PermissionsEnum::ViewAny,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
        ],
        Roles::Employee->value => [
            Appointment::class => [
                PermissionsEnum::View,
                PermissionsEnum::Create,
            ],
            Document::class => [
                PermissionsEnum::View,
                PermissionsEnum::ViewAny,
                PermissionsEnum::Create,
                PermissionsEnum::Update,
                PermissionsEnum::Delete,
            ],
            DocumentShare::class => [
                PermissionsEnum::View,
                PermissionsEnum::ViewAny,
            ],
        ],
    ],
];
