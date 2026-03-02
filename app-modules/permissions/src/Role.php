<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as BaseRole;
use TresPontosTech\Permissions\Database\Factories\RoleFactory;

#[UsePolicy(RolePolicy::class)]
/**
 * @property-read string $id
 * @property string $name
 * @property string $guard_name
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Collection|Permission[] $permissions
 */
class Role extends BaseRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;
}
