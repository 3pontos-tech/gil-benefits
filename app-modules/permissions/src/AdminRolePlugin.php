<?php

namespace TresPontosTech\Permissions;

use Filament\Contracts\Plugin;
use Filament\Panel;
use TresPontosTech\Permissions\Filament\Admin\Resources\Permissions\RoleResource;

class AdminRolePlugin implements Plugin
{

    public function getId(): string
    {
       return 'role-admin';
    }

    public function register(Panel $panel): void
    {
       $panel->resources([
           RoleResource::class
       ]);
    }

    public function boot(Panel $panel): void{}
}
