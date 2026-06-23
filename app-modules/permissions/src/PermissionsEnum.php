<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

enum PermissionsEnum: string
{
    case View = 'view';
    case ViewAny = 'view_any';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Restore = 'restore';
    case ForceDelete = 'force_delete';

    public function buildPermissionFor(string $classPath): string
    {
        $morphAlias = array_search($classPath, Relation::morphMap(), strict: true);

        $resource = $morphAlias === false ? $classPath : $morphAlias;

        return $this->value . '_' . Str::snake($resource);
    }
}
