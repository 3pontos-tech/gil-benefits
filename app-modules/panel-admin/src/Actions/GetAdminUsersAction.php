<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Actions;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use TresPontosTech\Permissions\Roles;

final readonly class GetAdminUsersAction
{
    /** @return Collection<int, User> */
    public static function execute(): Collection
    {
        return User::query()->role([Roles::SuperAdmin->value, Roles::Admin->value])
            ->get()
            ->unique('id')
            ->values();
    }
}
