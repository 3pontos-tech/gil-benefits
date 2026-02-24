<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions\Commands\SyncPermissions;

use TresPontosTech\Permissions\PermissionsEnum;

readonly class RolePermissions
{
    /**
     * @param  array<string, array<int, PermissionsEnum>>  $resources
     */
    public function __construct(
        public string $role,
        public array $resources,
    ) {}
}
